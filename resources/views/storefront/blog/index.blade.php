@extends('layouts.storefront.app')

@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $storeSetting = $store?->setting;
    $storeLogo = $storeSetting?->adminLogo();
    $storeLogoUrl = $storeLogo ? asset('storage/' . $storeLogo) : null;
    $homeUrl = $storeSlug ? url('/?store_slug=' . $storeSlug) : url('/');
    $productsUrl = $storeSlug ? url('/products?store_slug=' . $storeSlug) : url('/products');
    $orderBuilderUrl = $storeSlug ? url('/order-builder?store_slug=' . $storeSlug) : url('/order-builder');

    $isFirstPage = (int) request('page', 1) === 1;
    $hasActiveFilter = request()->filled('category') || request()->filled('search');

    // On page 1 with no active search/category filter, feature the top post prominently
    $featuredPost = ($isFirstPage && !$hasActiveFilter && $posts->count() > 0) ? $posts->first() : null;
    $gridPosts = $featuredPost ? $posts->slice(1) : $posts;

    $catIcons = [
        'mobile guide' => '📱',
        'accessories guide' => '🔌',
        'cctv guide' => '📹',
        'computer guide' => '💻',
        'network guide' => '🌐',
        'fashion guide' => '👗',
        'tips & tricks' => '💡',
    ];
@endphp

<div class="max-w-6xl mx-auto space-y-2 sm:space-y-3 select-none font-sans pb-16">

    {{-- 1. Store Header & Blog Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3 px-2 sm:px-3 py-1.5 sm:py-2 border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl shadow-2xs">
        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
            @if ($storeLogoUrl)
                <img src="{{ $storeLogoUrl }}" alt="{{ $store->name }}" class="h-8 w-8 sm:h-9 sm:w-9 rounded-md object-contain bg-white dark:bg-slate-800 p-0.5 border border-slate-200 dark:border-slate-700 shadow-2xs shrink-0" />
            @else
                <div class="sf-btn-3d active flex h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-md text-white font-black text-xs sm:text-sm shrink-0 pointer-events-none">
                    {{ mb_substr($store?->name ?? 'D', 0, 1) }}
                </div>
            @endif
            <div class="min-w-0">
                <h1 class="text-sm sm:text-lg font-black text-slate-900 dark:text-white flex items-center gap-1.5 sm:gap-2 flex-wrap font-sans">
                    <span class="truncate">{{ __('messages.blog_title') }}</span>
                    <span class="sf-btn-3d active inline-flex items-center px-1.5 py-0.2 rounded text-[10px] sm:text-[11px] font-black pointer-events-none whitespace-nowrap">
                        {{ $posts->total() }} {{ __('messages.blog_posts') }}
                    </span>
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium truncate">
                    {{ __('messages.blog_subtitle') }}
                </p>
            </div>
        </div>

        {{-- Top Actions: Search Form & Cart/Home Links --}}
        <div class="flex items-center gap-1.5 shrink-0 self-start sm:self-auto flex-wrap">
            {{-- Search Bar --}}
            <form action="{{ url('/blog') }}" method="GET" class="flex items-center gap-1">
                @if ($storeSlug)
                    <input type="hidden" name="store_slug" value="{{ $storeSlug }}">
                @endif
                @if (request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <div class="relative">
                    <input type="search"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="{{ __('messages.blog_search_placeholder') }}"
                           class="w-36 sm:w-44 lg:w-56 h-7 text-xs px-2 pl-6 rounded-md border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-sky-500 shadow-inner" />
                    <span class="absolute left-2 top-1.5 text-[11px] text-slate-400 pointer-events-none">🔍</span>
                </div>
                <button type="submit" class="sf-btn-3d !px-2 !py-1 rounded-md text-xs font-bold">
                    {{ __('messages.search') }}
                </button>
            </form>

            {{-- Cart Quick Link --}}
            <a href="{{ $orderBuilderUrl }}"
               class="sf-btn-3d-gold !inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-black shadow-2xs cursor-pointer"
               title="{{ __('messages.nav_cart') }}">
                <span aria-hidden="true">🛒</span>
                <span class="hidden sm:inline">{{ __('messages.nav_cart') }}</span>
                <span x-show="$store.orderBuilder && $store.orderBuilder.totalCount > 0"
                      class="px-1.5 py-0.2 rounded-full bg-white text-orange-600 font-black text-[10px]"
                      x-text="$store.orderBuilder ? $store.orderBuilder.totalCount : 0"></span>
            </a>

            {{-- Products / Home Link --}}
            <a href="{{ $productsUrl }}"
               class="sf-btn-3d !inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold"
               title="{{ __('messages.products') }}">
                <span aria-hidden="true">🛍️</span>
                <span class="hidden sm:inline">{{ __('messages.products') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. Category Filter Pills Bar --}}
    @if ($categories->count() > 0 || $hasActiveFilter)
        <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-2 sm:p-2.5 border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center justify-between gap-2 flex-wrap">
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-0.5 max-w-full">
                {{-- All Filter Pill --}}
                <a href="{{ url('/blog' . ($storeSlug ? '?store_slug=' . $storeSlug : '') . (request('search') ? '&search=' . urlencode(request('search')) : '')) }}"
                   class="{{ !request('category') ? 'sf-btn-3d-primary' : 'sf-btn-3d' }} !inline-flex items-center gap-1 px-3 py-1 rounded-md text-xs font-black shrink-0">
                    <span>📚</span>
                    <span>{{ __('messages.all') }}</span>
                </a>

                {{-- Category Specific Pills --}}
                @foreach ($categories as $cat)
                    @php
                        $catLower = strtolower(trim($cat));
                        $icon = $catIcons[$catLower] ?? '🏷️';
                        $isActive = request('category') === $cat;
                        $catUrl = url('/blog?' . http_build_query(array_filter([
                            'store_slug' => $storeSlug,
                            'category' => $cat,
                            'search' => request('search'),
                        ])));
                    @endphp
                    <a href="{{ $catUrl }}"
                       class="{{ $isActive ? 'sf-btn-3d-primary' : 'sf-btn-3d' }} !inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold shrink-0">
                        <span>{{ $icon }}</span>
                        <span>{{ $cat }}</span>
                    </a>
                @endforeach
            </div>

            {{-- Clear Filters button if search/category is active --}}
            @if ($hasActiveFilter)
                <a href="{{ url('/blog' . ($storeSlug ? '?store_slug=' . $storeSlug : '')) }}"
                   class="sf-btn-3d-danger !inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-bold shrink-0"
                   title="{{ __('messages.blog_clear_filter') }}">
                    <span>✕</span>
                    <span>{{ __('messages.blog_clear_filter') }}</span>
                </a>
            @endif
        </div>
    @endif

    {{-- 3. Featured Post Hero Card (Only on Page 1 without active filters) --}}
    @if ($featuredPost)
        @php
            $featuredUrl = url('/blog/' . $featuredPost->slug . ($storeSlug ? '?store_slug=' . $storeSlug : ''));
            $featuredReadMin = max(1, round(mb_strlen(strip_tags($featuredPost->content)) / 400));
            $featuredCatLower = strtolower(trim($featuredPost->category ?? ''));
            $featuredCatIcon = $catIcons[$featuredCatLower] ?? '🏷️';
        @endphp
        <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs p-2.5 sm:p-4 hover:shadow-sm transition">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 sm:gap-4 items-center">
                {{-- Hero Visual / Image --}}
                <div class="md:col-span-6 lg:col-span-5 relative aspect-[16/10] rounded-lg sm:rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-800 shadow-2xs group">
                    <a href="{{ $featuredUrl }}" class="block w-full h-full">
                        @if ($featuredPost->image_path)
                            <img src="{{ asset('storage/' . $featuredPost->image_path) }}"
                                 alt="{{ $featuredPost->title }}"
                                 loading="eager"
                                 decoding="async"
                                 class="w-full h-full object-cover transition duration-300 group-hover:scale-105" />
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-sky-100 via-indigo-50 to-slate-100 dark:from-slate-800 dark:via-slate-850 dark:to-slate-900 text-slate-700 dark:text-slate-200">
                                <span class="text-4xl sm:text-5xl drop-shadow-sm">📝</span>
                                <span class="text-xs font-black mt-2 text-slate-500 uppercase tracking-wider">Storefront Blog</span>
                            </div>
                        @endif
                    </a>

                    {{-- Badges Overlay --}}
                    <div class="absolute top-2 left-2 flex items-center gap-1.5 flex-wrap">
                        <span class="sf-btn-3d active !inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-black pointer-events-none shadow-2xs">
                            <span>⭐</span>
                            <span>{{ __('messages.blog_featured_badge') }}</span>
                        </span>
                    </div>

                    @if ($featuredPost->category)
                        <span class="absolute bottom-2 right-2 inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-950/80 text-white text-[10px] font-bold backdrop-blur-xs shadow-2xs">
                            <span>{{ $featuredCatIcon }}</span>
                            <span>{{ $featuredPost->category }}</span>
                        </span>
                    @endif
                </div>

                {{-- Hero Content Details --}}
                <div class="md:col-span-6 lg:col-span-7 flex flex-col justify-between space-y-2 sm:space-y-3">
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2 text-[11px] font-bold text-slate-500 dark:text-slate-400">
                            <span class="inline-flex items-center gap-1">
                                <span>📅</span>
                                <span>{{ $featuredPost->published_at?->format('M j, Y') ?? $featuredPost->created_at->format('M j, Y') }}</span>
                            </span>
                            <span>•</span>
                            <span class="inline-flex items-center gap-1 text-sky-600 dark:text-sky-400">
                                <span>⏱️</span>
                                <span>{{ $featuredReadMin }} {{ __('messages.blog_read_time') }}</span>
                            </span>
                        </div>

                        <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white leading-tight hover:text-sky-600 dark:hover:text-sky-400 transition">
                            <a href="{{ $featuredUrl }}">{{ $featuredPost->title }}</a>
                        </h2>

                        @if ($featuredPost->excerpt)
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 font-medium leading-relaxed line-clamp-3">
                                {{ $featuredPost->excerpt }}
                            </p>
                        @endif
                    </div>

                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2 flex-wrap">
                        <a href="{{ $featuredUrl }}"
                           class="sf-btn-3d-primary !inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-md text-xs font-black shadow-2xs">
                            <span>📖</span>
                            <span>{{ __('messages.blog_read_more') }}</span>
                            <span aria-hidden="true">→</span>
                        </a>

                        <div class="text-[11px] font-mono text-slate-400">
                            #{{ $featuredPost->slug }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 4. Main Articles Card Grid --}}
    @if ($gridPosts->count() > 0)
        <div class="space-y-2">
            @if ($featuredPost)
                <div class="flex items-center justify-between pt-1">
                    <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white flex items-center gap-1.5 uppercase tracking-wider">
                        <span>📰</span>
                        <span>{{ __('messages.blog_latest_articles') }}</span>
                    </h3>
                    <span class="text-[10px] font-mono text-slate-400">
                        {{ $gridPosts->count() }} {{ __('messages.blog_posts') }}
                    </span>
                </div>
            @endif

            {{-- Modern Responsive Card Grid (1 col on mobile, 2 on sm, 3 on lg) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">
                @foreach ($gridPosts as $post)
                    @php
                        $postUrl = url('/blog/' . $post->slug . ($storeSlug ? '?store_slug=' . $storeSlug : ''));
                        $readMinutes = max(1, round(mb_strlen(strip_tags($post->content)) / 400));
                        $catLower = strtolower(trim($post->category ?? ''));
                        $catIcon = $catIcons[$catLower] ?? '🏷️';
                    @endphp
                    <article class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl border border-slate-200/90 dark:border-slate-800 p-2 sm:p-2.5 flex flex-col gap-2 overflow-hidden shadow-2xs hover:shadow-xs transition group">
                        {{-- Image / Visual Container --}}
                        <div class="relative -mx-2 -mt-2 sm:-mx-2.5 sm:-mt-2.5 aspect-[16/10] overflow-hidden bg-slate-100 dark:bg-slate-800 rounded-t-lg sm:rounded-t-xl">
                            <a href="{{ $postUrl }}" class="block w-full h-full">
                                @if ($post->image_path)
                                    <img src="{{ asset('storage/' . $post->image_path) }}"
                                         alt="{{ $post->title }}"
                                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                         loading="lazy"
                                         decoding="async" />
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-3xl bg-gradient-to-br from-slate-100 via-sky-50 to-indigo-50 dark:from-slate-800 dark:to-slate-900 text-slate-600 dark:text-slate-300">
                                        {{ $catIcon }}
                                    </div>
                                @endif
                            </a>

                            {{-- Date Badge (Top-Left) --}}
                            <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] font-bold bg-slate-900/80 text-white backdrop-blur-xs shadow-2xs">
                                📅 {{ $post->published_at?->format('M j, Y') ?? $post->created_at->format('M j, Y') }}
                            </span>

                            {{-- Category Badge (Top-Right) --}}
                            @if ($post->category)
                                <a href="{{ url('/blog?' . http_build_query(array_filter(['store_slug' => $storeSlug, 'category' => $post->category]))) }}"
                                   class="absolute top-1.5 right-1.5 px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] font-bold bg-sky-600/90 text-white backdrop-blur-xs shadow-2xs hover:bg-sky-500">
                                    {{ $catIcon }} {{ $post->category }}
                                </a>
                            @endif
                        </div>

                        {{-- Card Body --}}
                        <div class="flex-1 flex flex-col space-y-1">
                            <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-500 dark:text-slate-400">
                                <span>⏱️ {{ $readMinutes }} {{ __('messages.blog_read_time') }}</span>
                            </div>

                            <h4 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white leading-snug line-clamp-2 group-hover:text-sky-600 dark:group-hover:text-sky-400 transition">
                                <a href="{{ $postUrl }}">{{ $post->title }}</a>
                            </h4>

                            @if ($post->excerpt)
                                <p class="text-[11px] sm:text-xs text-slate-600 dark:text-slate-400 font-medium leading-relaxed line-clamp-2 flex-1">
                                    {{ $post->excerpt }}
                                </p>
                            @endif
                        </div>

                        {{-- Card Bottom Row --}}
                        <div class="pt-1.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1 mt-auto">
                            <a href="{{ $postUrl }}"
                               class="sf-btn-3d flex-1 py-1 rounded-md text-xs font-bold flex items-center justify-center gap-1">
                                <span>{{ __('messages.blog_read_more') }}</span>
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            {{-- 5. Pagination --}}
            @if ($posts->hasPages())
                <div class="pt-2">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>
    @elseif (!$featuredPost)
        {{-- Empty Search or Blog State --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-8 sm:p-12 text-center text-slate-500 dark:text-slate-400 space-y-3 border border-slate-200/90 dark:border-slate-800 shadow-2xs">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-500 flex items-center justify-center text-2xl sm:text-3xl mx-auto shadow-inner border border-sky-200/80 dark:border-sky-900/60">
                📝
            </div>
            <div class="space-y-1">
                <h3 class="text-sm sm:text-base font-black text-slate-800 dark:text-slate-200">
                    {{ request('search') ? __('messages.blog_no_results') : __('messages.blog_empty') }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    {{ request('search') ? 'ကျေးဇူးပြု၍ အခြားသော စကားလုံး သို့မဟုတ် ခေါင်းစဉ်ဖြင့် ပြန်လည်ရှာဖွေကြည့်ပါ' : 'မကြာမီ သတင်းနှင့် ဆောင်းပါးအသစ်များကို တင်ဆက်ပေးပါမည်' }}
                </p>
            </div>
            <div class="flex items-center justify-center gap-2 pt-2 flex-wrap">
                @if ($hasActiveFilter)
                    <a href="{{ url('/blog' . ($storeSlug ? '?store_slug=' . $storeSlug : '')) }}"
                       class="sf-btn-3d-primary !inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-md text-xs font-black shadow-xs">
                        <span>✕</span>
                        <span>{{ __('messages.blog_clear_filter') }}</span>
                    </a>
                @endif
                <a href="{{ $productsUrl }}"
                   class="sf-btn-3d !inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-md text-xs font-bold">
                    <span>🛍️</span>
                    <span>{{ __('messages.products') }}</span>
                </a>
            </div>
        </div>
    @endif

    {{-- 6. Storefront Trust Highlights Row --}}
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
@endsection
