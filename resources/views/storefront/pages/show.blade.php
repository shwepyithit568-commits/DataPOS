@extends('layouts.storefront.app')

@section('title', $title . ' - ' . ($store->setting?->store_name ?? $store->name))

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
    $blogUrl = url('/blog' . ($storeSlug ? '?store_slug=' . $storeSlug : ''));

    // Store Contact Numbers
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
    $shareTitle = $title . ' — ' . ($store->setting?->store_name ?? $store->name);
    $facebookShareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($canonicalUrl);
    $telegramShareUrl = 'https://t.me/share/url?url=' . urlencode($canonicalUrl) . '&text=' . urlencode($shareTitle);
    $viberShareUrl = 'viber://forward?text=' . urlencode($shareTitle . ' ' . $canonicalUrl);

    // Page Icon Mapper (guarded: blade views may render more than once per process)
    if (!function_exists('getPageIcon')) {
        function getPageIcon($slug) {
            return match (strtolower(trim($slug))) {
                'about-us' => 'ℹ️',
                'terms-and-conditions' => '📜',
                'privacy-policy' => '🔒',
                'refund-policy' => '💵',
                'shipping-policy' => '🚚',
                default => '📄',
            };
        }
    }
@endphp

<div class="max-w-4xl mx-auto space-y-2 sm:space-y-3.5 select-none font-sans pb-16"
     x-data="{
         copied: false,
         toc: [],
         tocOpen: true,
         init() {
             this.$nextTick(() => {
                 const headings = document.querySelectorAll('.sf-page-content h2, .sf-page-content h3');
                 headings.forEach((h, index) => {
                     if (!h.id) {
                         h.id = 'section-' + (index + 1);
                     }
                     this.toc.push({
                         id: h.id,
                         text: h.innerText.replace(/^#+\s*/, ''),
                         level: h.tagName.toLowerCase()
                     });
                 });
             });
         },
         scrollTo(id) {
             const target = document.getElementById(id);
             if (target) {
                 target.scrollIntoView({ behavior: 'smooth', block: 'start' });
             }
         }
     }">

    {{-- 1. Top Breadcrumb & Store Header Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-3 px-2 sm:px-3 py-1.5 sm:py-2 border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl shadow-2xs">
        <div class="flex items-center gap-2 min-w-0 flex-wrap">
            <a href="{{ $homeUrl }}"
               class="sf-btn-3d !inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold"
               title="{{ __('messages.home') }}">
                <span aria-hidden="true">🏠</span>
                <span>{{ __('messages.home') }}</span>
            </a>

            <span class="text-slate-300 dark:text-slate-700">/</span>

            <span class="sf-btn-3d active !inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-black pointer-events-none shadow-2xs">
                <span>{{ getPageIcon($page->slug) }}</span>
                <span class="truncate max-w-[140px] sm:max-w-xs">{{ $title }}</span>
            </span>
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

    {{-- 2. Store CMS Policies Switcher Pills --}}
    @if (isset($otherPages) && $otherPages->count() > 1)
        <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-2 sm:p-2.5 border border-slate-200/90 dark:border-slate-800 shadow-2xs flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1">
            <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1 pr-1 shrink-0 flex items-center gap-1">
                <span>📄</span>
                <span class="hidden sm:inline">{{ __('messages.cms_store_policies') }}:</span>
            </span>
            @foreach ($otherPages as $op)
                @php
                    $opLocale = app()->getLocale();
                    $opTitle = $op->localizedTitle($opLocale);
                    $opUrl = url('/store/' . $store->slug . '/page/' . $op->slug);
                    $isCurrent = $op->id === $page->id;
                    $opIcon = getPageIcon($op->slug);
                @endphp
                <a href="{{ $opUrl }}"
                   class="{{ $isCurrent ? 'sf-btn-3d-primary' : 'sf-btn-3d' }} !inline-flex items-center gap-1 px-3 py-1 rounded-md text-xs {{ $isCurrent ? 'font-black' : 'font-bold' }} shrink-0">
                    <span>{{ $opIcon }}</span>
                    <span>{{ $opTitle }}</span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- 3. Main Page Content Card --}}
    <article class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg sm:rounded-2xl p-3 sm:p-6 lg:p-7 shadow-xs space-y-3 sm:space-y-4">
        
        {{-- Page Header --}}
        <header class="border-b border-slate-100 dark:border-slate-800 pb-3 sm:pb-4 space-y-2">
            <div class="flex items-center gap-2 flex-wrap text-xs text-slate-500 dark:text-slate-400">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-black bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200/60 dark:border-sky-900/60">
                    <span>{{ getPageIcon($page->slug) }}</span>
                    <span>Official Policy</span>
                </span>

                @if ($page->published_at)
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 dark:text-slate-400">
                        <span>🕒</span>
                        <span>{{ __('messages.cms_last_updated') }}:</span>
                        <time datetime="{{ $page->published_at->toISOString() }}" class="font-mono">
                            {{ $page->published_at->format('M d, Y') }}
                        </time>
                    </span>
                @endif
            </div>

            <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-tight font-sans">
                {{ $title }}
            </h1>

            @if (!empty($summary))
                <div class="bg-sky-50/70 dark:bg-sky-950/40 border-l-4 border-sky-500 p-3 sm:p-3.5 rounded-r-lg text-xs sm:text-sm text-slate-700 dark:text-slate-300 font-medium font-myanmar leading-relaxed">
                    {{ $summary }}
                </div>
            @endif
        </header>

        {{-- Featured Image (if available) --}}
        @if (!empty($page->featured_image_path))
            <div class="overflow-hidden rounded-lg sm:rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 aspect-[21/9]">
                <img src="{{ asset('storage/' . $page->featured_image_path) }}"
                     alt="{{ $title }}"
                     class="w-full h-full object-cover shadow-xs"
                     loading="eager" />
            </div>
        @endif

        {{-- 4. Table of Contents Quick Nav (Dynamically Populated from H2/H3) --}}
        <div x-show="toc.length > 0"
             x-cloak
             class="p-2.5 sm:p-3.5 rounded-lg sm:rounded-xl bg-slate-50/80 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 space-y-2">
            <div class="flex items-center justify-between cursor-pointer select-none"
                 @click="tocOpen = !tocOpen">
                <span class="text-xs font-black text-slate-800 dark:text-slate-200 flex items-center gap-1.5 uppercase tracking-wider">
                    <span>📑</span>
                    <span>{{ __('messages.table_of_contents') }}</span>
                    <span class="text-[10px] font-mono px-1.5 py-0.2 rounded-full bg-white dark:bg-slate-700 text-slate-600 dark:text-slate-300"
                          x-text="toc.length"></span>
                </span>
                <button type="button" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-mono">
                    <span x-show="tocOpen">▲</span>
                    <span x-show="!tocOpen">▼</span>
                </button>
            </div>

            <div x-show="tocOpen" class="pt-1">
                <nav class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                    <template x-for="(item, index) in toc" :key="item.id">
                        <button type="button"
                                @click="scrollTo(item.id)"
                                class="text-left py-1 px-2 rounded-md hover:bg-white dark:hover:bg-slate-700/80 transition flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300 hover:text-sky-600 dark:hover:text-sky-400 group">
                            <span class="font-mono text-[10px] text-sky-500 font-bold shrink-0" x-text="(index + 1) + '.'"></span>
                            <span class="truncate font-medium font-myanmar group-hover:underline" x-text="item.text"></span>
                        </button>
                    </template>
                </nav>
            </div>
        </div>

        {{-- 5. Markdown Rendered Content --}}
        <div class="sf-page-content text-slate-800 dark:text-slate-200 text-sm sm:text-base leading-relaxed sm:leading-loose font-myanmar space-y-3 selection:bg-sky-500 selection:text-white pt-1">
            {!! $renderedContent !!}
        </div>

        {{-- 6. Social Share & 1-Tap Copy Link 3D Toolbar --}}
        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div class="flex items-center gap-1.5 text-xs font-black text-slate-700 dark:text-slate-300">
                <span>📣</span>
                <span>{{ __('messages.share') }}:</span>
            </div>

            <div class="flex items-center gap-1.5 flex-wrap">
                {{-- 1-Tap Copy Link 3D Button --}}
                <button type="button"
                        @click="navigator.clipboard.writeText('{{ $canonicalUrl }}'); copied = true; setTimeout(() => copied = false, 2500);"
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
    </article>

    {{-- 7. Shop Consultation Hotline Banner --}}
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

    {{-- 8. Storefront Trust Highlights Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2 text-center text-xs pt-1">
        <div class="p-2 sm:p-2.5 rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-sm sm:text-base">🛡️</span>
            <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">100% Authentic</p>
        </div>
        <div class="p-2 sm:p-2.5 rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-sm sm:text-base">📜</span>
            <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">Clear Policies</p>
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

<style>
    /* Clean, highly-readable typography for rendered markdown */
    .sf-page-content h1 { font-size: 1.35rem; font-weight: 900; margin-top: 1.5rem; margin-bottom: 0.75rem; color: var(--sf-primary, #0ea5e9); line-height: 1.3; }
    .sf-page-content h2 { font-size: 1.2rem; font-weight: 800; margin-top: 1.25rem; margin-bottom: 0.5rem; color: #0f172a; line-height: 1.35; padding-top: 0.5rem; scroll-margin-top: 5rem; }
    .dark .sf-page-content h2 { color: #f8fafc; }
    .sf-page-content h3 { font-size: 1.05rem; font-weight: 700; margin-top: 1rem; margin-bottom: 0.5rem; color: #334155; scroll-margin-top: 5rem; }
    .dark .sf-page-content h3 { color: #e2e8f0; }
    .sf-page-content p { margin-bottom: 0.85rem; line-height: 1.8; }
    .sf-page-content ul { list-style-type: disc; margin-left: 1.25rem; margin-bottom: 0.85rem; space-y: 0.25rem; }
    .sf-page-content ol { list-style-type: decimal; margin-left: 1.25rem; margin-bottom: 0.85rem; space-y: 0.25rem; }
    .sf-page-content li { margin-bottom: 0.35rem; line-height: 1.7; }
    .sf-page-content strong { font-weight: 800; color: #0f172a; }
    .dark .sf-page-content strong { color: #ffffff; }
    .sf-page-content a { color: var(--sf-primary, #0ea5e9); font-weight: 700; text-decoration: underline; text-underline-offset: 2px; }
    .sf-page-content blockquote { border-left: 4px solid var(--sf-primary, #0ea5e9); padding-left: 1rem; font-style: italic; color: #64748b; margin: 1rem 0; background: rgba(14, 165, 233, 0.05); padding-top: 0.5rem; padding-bottom: 0.5rem; border-radius: 0 0.5rem 0.5rem 0; }
    .dark .sf-page-content blockquote { color: #94a3b8; background: rgba(14, 165, 233, 0.1); }
    .sf-page-content code { background-color: #f1f5f9; padding: 0.15rem 0.4rem; border-radius: 0.375rem; font-family: monospace; font-size: 0.875em; color: #0f172a; }
    .dark .sf-page-content code { background-color: #1e293b; color: #f8fafc; }
    .sf-page-content pre { background-color: #0f172a; color: #f8fafc; padding: 1rem; border-radius: 0.75rem; overflow-x: auto; margin-bottom: 1rem; }
    .sf-page-content pre code { background-color: transparent; padding: 0; color: inherit; }
    .sf-page-content table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; font-size: 0.875rem; }
    .sf-page-content th, .sf-page-content td { border: 1px solid #e2e8f0; padding: 0.5rem 0.75rem; text-align: left; }
    .dark .sf-page-content th, .dark .sf-page-content td { border-color: #334155; }
    .sf-page-content th { background-color: #f8fafc; font-weight: 800; color: #0f172a; }
    .dark .sf-page-content th { background-color: #1e293b; color: #f8fafc; }
</style>
@endsection
