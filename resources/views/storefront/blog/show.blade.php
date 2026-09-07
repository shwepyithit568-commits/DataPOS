@extends('layouts.storefront.app')

@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $storeSetting = $store?->setting;
    $storeLogo = $storeSetting?->adminLogo();
    $storeLogoUrl = $storeLogo ? asset('storage/' . $storeLogo) : null;
    $blogUrl = url('/blog' . ($storeSlug ? '?store_slug=' . $storeSlug : ''));
    $productsUrl = $storeSlug ? url('/products?store_slug=' . $storeSlug) : url('/products');
    $orderBuilderUrl = $storeSlug ? url('/order-builder?store_slug=' . $storeSlug) : url('/order-builder');
    $postUrl = url('/blog/' . $post->slug . ($storeSlug ? '?store_slug=' . $storeSlug : ''));

    $readMinutes = max(1, round(mb_strlen(strip_tags($post->content)) / 400));
    $catLower = strtolower(trim($post->category ?? ''));
    $catIcons = [
        'mobile guide' => '📱',
        'accessories guide' => '🔌',
        'cctv guide' => '📹',
        'computer guide' => '💻',
        'network guide' => '🌐',
        'fashion guide' => '👗',
        'tips & tricks' => '💡',
    ];
    $catIcon = $catIcons[$catLower] ?? '🏷️';

    // Store Contact Numbers & Channels
    $rawPhone = $storeSetting?->hotline_phone ?? $store?->phone ?? '09100000001';
    $rawViber = $storeSetting?->viber_number ?? $store?->phone ?? $rawPhone;
    $rawTelegram = $storeSetting?->telegram_handle ?? '';

    $cleanPhone = preg_replace('/[^0-9]/', '', $rawViber);
    if (str_starts_with($cleanPhone, '09')) {
        $viberLink = 'viber://chat?number=%2B959' . substr($cleanPhone, 2);
    } elseif (str_starts_with($cleanPhone, '959')) {
        $viberLink = 'viber://chat?number=%2B' . $cleanPhone;
    } else {
        $viberLink = 'viber://chat?number=%2B95' . ltrim($cleanPhone, '0');
    }

    $telegramLink = $rawTelegram ? 'https://t.me/' . ltrim($rawTelegram, '@') : null;
    $phoneLink = 'tel:' . preg_replace('/[^\+0-9]/', '', $rawPhone);

    // Social Sharing Links
    $shareTitle = $post->title . ' — ' . ($store?->name ?? config('app.name'));
    $facebookShareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($postUrl);
    $telegramShareUrl = 'https://t.me/share/url?url=' . urlencode($postUrl) . '&text=' . urlencode($shareTitle);
    $viberShareUrl = 'viber://forward?text=' . urlencode($shareTitle . ' ' . $postUrl);
@endphp

<div class="max-w-4xl mx-auto space-y-2 sm:space-y-3.5 select-none font-sans pb-16">

    {{-- 1. Top Breadcrumb & Navigation Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3 px-2 sm:px-3 py-1.5 sm:py-2 border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl shadow-2xs">
        <div class="flex items-center gap-2 min-w-0 flex-wrap">
            <a href="{{ $blogUrl }}"
               class="sf-btn-3d !inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold"
               title="{{ __('messages.blog_back') }}">
                <span aria-hidden="true">←</span>
                <span>{{ __('messages.blog_back') }}</span>
            </a>

            @if ($post->category)
                <a href="{{ url('/blog?' . http_build_query(array_filter(['store_slug' => $storeSlug, 'category' => $post->category]))) }}"
                   class="sf-btn-3d active !inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-black shadow-2xs">
                    <span>{{ $catIcon }}</span>
                    <span>{{ $post->category }}</span>
                </a>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-1.5 shrink-0 self-start sm:self-auto flex-wrap">
            <a href="{{ $orderBuilderUrl }}"
               class="sf-btn-3d-gold !inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-black shadow-2xs cursor-pointer"
               title="{{ __('messages.nav_cart') }}">
                <span aria-hidden="true">🛒</span>
                <span class="hidden sm:inline">{{ __('messages.nav_cart') }}</span>
                <span x-show="$store.orderBuilder && $store.orderBuilder.totalCount > 0"
                      class="px-1.5 py-0.2 rounded-full bg-white text-orange-600 font-black text-[10px]"
                      x-text="$store.orderBuilder ? $store.orderBuilder.totalCount : 0"></span>
            </a>

            <a href="{{ $productsUrl }}"
               class="sf-btn-3d !inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold"
               title="{{ __('messages.products') }}">
                <span aria-hidden="true">🛍️</span>
                <span class="hidden sm:inline">{{ __('messages.products') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. Main Article Card --}}
    <article class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-2xl overflow-hidden border border-slate-200/90 dark:border-slate-800 shadow-xs">
        
        {{-- Hero Thumbnail Image / Fallback Container --}}
        @if ($post->image_path)
            <div class="aspect-[16/8] sm:aspect-[21/9] overflow-hidden bg-slate-100 dark:bg-slate-800 relative">
                <img src="{{ asset('storage/' . $post->image_path) }}"
                     alt="{{ $post->title }}"
                     class="w-full h-full object-cover"
                     loading="eager"
                     decoding="async" />
                @if ($post->category)
                    <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded bg-slate-950/80 text-white text-[10px] font-bold backdrop-blur-xs shadow-2xs">
                        {{ $catIcon }} {{ $post->category }}
                    </span>
                @endif
            </div>
        @endif

        <div class="p-3 sm:p-6 lg:p-7 space-y-3 sm:space-y-4">
            
            {{-- Metadata Row (Date, Read Time, Store Badge) --}}
            <div class="flex items-center gap-2 flex-wrap text-xs text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span>📅</span>
                    <span>{{ $post->published_at?->format('F j, Y') ?? $post->created_at->format('F j, Y') }}</span>
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-bold bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200/60 dark:border-sky-900/60">
                    <span>⏱️</span>
                    <span>{{ $readMinutes }} {{ __('messages.blog_read_time') }}</span>
                </span>
                @if ($store)
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-400">
                        <span>🏪</span>
                        <span>{{ $store->name }}</span>
                    </span>
                @endif
            </div>

            {{-- Post Title --}}
            <h1 class="text-lg sm:text-2xl lg:text-3xl font-black text-slate-900 dark:text-white leading-tight font-sans">
                {{ $post->title }}
            </h1>

            {{-- Excerpt Callout (If Available) --}}
            @if ($post->excerpt)
                <div class="bg-sky-50/70 dark:bg-sky-950/40 border-l-4 border-sky-500 p-3 sm:p-3.5 rounded-r-lg text-xs sm:text-sm text-slate-700 dark:text-slate-300 font-medium font-myanmar leading-relaxed">
                    {{ $post->excerpt }}
                </div>
            @endif

            {{-- Body Content with High-Readability Typography --}}
            <div class="prose prose-sm sm:prose dark:prose-invert max-w-none font-myanmar text-slate-800 dark:text-slate-200 leading-relaxed sm:leading-loose selection:bg-sky-500 selection:text-white space-y-3 pt-1">
                @if (preg_match('/<[a-z][\s\S]*>/i', $post->content))
                    {!! $post->content !!}
                @else
                    {!! nl2br(e($post->content)) !!}
                @endif
            </div>

            {{-- Tags Row --}}
            @if ($post->tags)
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center gap-1.5 flex-wrap">
                    <span class="text-xs font-bold text-slate-400">Tags:</span>
                    @foreach (array_filter(array_map('trim', explode(',', $post->tags))) as $tag)
                        <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                            #{{ $tag }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- 3. Social Share 3D Buttons Toolbar --}}
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5"
                 x-data="{ copied: false }">
                <div class="flex items-center gap-1.5 text-xs font-black text-slate-700 dark:text-slate-300">
                    <span>📣</span>
                    <span>{{ __('messages.share') }}:</span>
                </div>

                <div class="flex items-center gap-1.5 flex-wrap">
                    {{-- 1-Tap Copy Link 3D Button --}}
                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ $postUrl }}'); copied = true; setTimeout(() => copied = false, 2500);"
                            class="sf-btn-3d !inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-bold cursor-pointer"
                            title="{{ __('messages.copy_link') }}">
                        <span x-show="!copied">🔗</span>
                        <span x-show="copied" class="text-emerald-500 font-black">✓</span>
                        <span x-text="copied ? '{{ __('messages.copied') }}' : '{{ __('messages.copy_link') }}'"></span>
                    </button>

                    {{-- Facebook Share 3D Button --}}
                    <a href="{{ $facebookShareUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="sf-btn-3d-facebook !inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-black shadow-2xs"
                       title="{{ __('messages.share_on_facebook') }}">
                        <span class="font-black text-xs">f</span>
                        <span>Facebook</span>
                    </a>

                    {{-- Viber Share 3D Button --}}
                    <a href="{{ $viberShareUrl }}"
                       class="sf-btn-3d-viber !inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-black shadow-2xs"
                       title="{{ __('messages.share_on_viber') }}">
                        <span>💬</span>
                        <span>Viber</span>
                    </a>

                    {{-- Telegram Share 3D Button --}}
                    <a href="{{ $telegramShareUrl }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="sf-btn-3d-telegram !inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-black shadow-2xs"
                       title="{{ __('messages.share_on_telegram') }}">
                        <span>✈️</span>
                        <span>Telegram</span>
                    </a>
                </div>
            </div>

        </div>
    </article>

    {{-- 4. Shop Help & Direct Consultation Banner --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-slate-200/90 dark:border-slate-800 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="space-y-0.5">
            <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5">
                <span>💡</span>
                <span>{{ __('messages.blog_help_contact_title') }}</span>
            </h3>
            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium font-myanmar">
                {{ __('messages.blog_help_contact_subtitle') }}
            </p>
        </div>

        <div class="flex items-center gap-1.5 flex-wrap shrink-0">
            <a href="{{ $viberLink }}"
               class="sf-btn-3d-viber !inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-black shadow-2xs">
                <span>💬</span>
                <span>Viber</span>
            </a>

            @if ($telegramLink)
                <a href="{{ $telegramLink }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="sf-btn-3d-telegram !inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-black shadow-2xs">
                    <span>✈️</span>
                    <span>Telegram</span>
                </a>
            @endif

            <a href="{{ $phoneLink }}"
               class="sf-btn-3d-success !inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-black shadow-2xs">
                <span>📞</span>
                <span>{{ __('messages.contact') }}</span>
            </a>

            <a href="{{ $productsUrl }}"
               class="sf-btn-3d-primary !inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-black shadow-2xs">
                <span>🛍️</span>
                <span>{{ __('messages.products') }}</span>
            </a>
        </div>
    </div>

    {{-- 5. Prev / Next Article 3D Navigation Cards --}}
    @if ($prevPost || $nextPost)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
            @if ($prevPost)
                @php
                    $prevUrl = url('/blog/' . $prevPost->slug . ($storeSlug ? '?store_slug=' . $storeSlug : ''));
                @endphp
                <a href="{{ $prevUrl }}"
                   class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-2.5 sm:p-3.5 border border-slate-200/90 dark:border-slate-800 hover:shadow-xs transition flex flex-col gap-1 group">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <span>←</span>
                        <span>{{ __('messages.blog_previous') }}</span>
                    </span>
                    <span class="text-xs sm:text-sm font-extrabold text-slate-800 dark:text-slate-200 line-clamp-2 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition">
                        {{ $prevPost->title }}
                    </span>
                </a>
            @else
                <div></div>
            @endif

            @if ($nextPost)
                @php
                    $nextUrl = url('/blog/' . $nextPost->slug . ($storeSlug ? '?store_slug=' . $storeSlug : ''));
                @endphp
                <a href="{{ $nextUrl }}"
                   class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-2.5 sm:p-3.5 border border-slate-200/90 dark:border-slate-800 hover:shadow-xs transition flex flex-col gap-1 group sm:text-right">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider flex items-center justify-end gap-1">
                        <span>{{ __('messages.blog_next') }}</span>
                        <span>→</span>
                    </span>
                    <span class="text-xs sm:text-sm font-extrabold text-slate-800 dark:text-slate-200 line-clamp-2 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition">
                        {{ $nextPost->title }}
                    </span>
                </a>
            @endif
        </div>
    @endif

    {{-- 6. Related Articles Cards Grid --}}
    @if ($related->count() > 0)
        <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-3 sm:p-4 border border-slate-200/90 dark:border-slate-800 shadow-2xs space-y-2.5">
            <div class="flex items-center justify-between">
                <h2 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 uppercase tracking-wider">
                    <span>📰</span>
                    <span>{{ __('messages.blog_related') }}</span>
                </h2>
                <a href="{{ $blogUrl }}" class="text-[11px] font-bold text-sky-600 dark:text-sky-400 hover:underline">
                    {{ __('messages.view_all') }} →
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-2.5">
                @foreach ($related as $rel)
                    @php
                        $relUrl = url('/blog/' . $rel->slug . ($storeSlug ? '?store_slug=' . $storeSlug : ''));
                        $relCatLower = strtolower(trim($rel->category ?? ''));
                        $relCatIcon = $catIcons[$relCatLower] ?? '🏷️';
                    @endphp
                    <a href="{{ $relUrl }}"
                       class="group bg-slate-50/70 dark:bg-slate-800/60 rounded-lg border border-slate-200/90 dark:border-slate-700/80 p-2 flex flex-col gap-1.5 overflow-hidden shadow-2xs hover:shadow-xs transition">
                        @if ($rel->image_path)
                            <div class="relative -mx-2 -mt-2 aspect-[16/9] overflow-hidden bg-slate-100 dark:bg-slate-800 rounded-t-lg">
                                <img src="{{ asset('storage/' . $rel->image_path) }}"
                                     alt="{{ $rel->title }}"
                                     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                     loading="lazy" />
                            </div>
                        @endif
                        <div class="flex-1 space-y-1">
                            @if ($rel->category)
                                <span class="text-[9px] font-extrabold text-sky-600 dark:text-sky-400 uppercase">
                                    {{ $relCatIcon }} {{ $rel->category }}
                                </span>
                            @endif
                            <h3 class="text-xs font-black text-slate-800 dark:text-slate-100 leading-snug line-clamp-2 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition">
                                {{ $rel->title }}
                            </h3>
                        </div>
                        <div class="pt-1 text-[11px] font-bold text-sky-600 dark:text-sky-400 flex items-center justify-between border-t border-slate-200/60 dark:border-slate-700/60 mt-auto">
                            <span>{{ __('messages.blog_read_more') }}</span>
                            <span>→</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 7. Storefront Trust Highlights Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2 text-center text-xs pt-1">
        <div class="p-2 sm:p-2.5 rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-sm sm:text-base">🛡️</span>
            <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">100% Authentic</p>
        </div>
        <div class="p-2 sm:p-2.5 rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-sm sm:text-base">💡</span>
            <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">Expert Guidance</p>
        </div>
        <div class="p-2 sm:p-2.5 rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-sm sm:text-base">🚚</span>
            <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">Fast Delivery</p>
        </div>
        <div class="p-2 sm:p-2.5 rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-sm sm:text-base">⭐</span>
            <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">Tech Support</p>
        </div>
    </div>

</div>

{{-- Article Structured Data for Google Rich Results --}}
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
