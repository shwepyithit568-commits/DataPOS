@extends('layouts.storefront.app')

@section('noMainPadding', true)

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $storeDisplayName = $setting?->store_name ?? $store?->name ?? config('app.name');
    $user = auth()->user();
    $isWholesaleApproved = $user && (
        $user->isPlatformOwner() ||
        ($store && $user->getStoreRole($store->id) === 'wholesale_customer')
    );
@endphp

<div class="space-y-[2px] sm:space-y-[3px] lg:space-y-[4px]">
    {{-- 1. Premium Ultra-Smooth Interactive Hero Carousel Slider --}}
    @if ($banners->count() > 0)
        <div 
            x-data="{
                activeSlide: 0,
                totalSlides: {{ $banners->count() }},
                timer: null,
                init() {
                    this.start();
                },
                start() {
                    if (this.totalSlides > 1) {
                        this.timer = setInterval(() => {
                            this.next();
                        }, 5000);
                    }
                },
                stop() {
                    if (this.timer) clearInterval(this.timer);
                },
                next() {
                    this.activeSlide = (this.activeSlide + 1) % this.totalSlides;
                },
                prev() {
                    this.activeSlide = (this.activeSlide - 1 + this.totalSlides) % this.totalSlides;
                }
            }"
            @mouseenter="stop()"
            @mouseleave="start()"
            class="relative overflow-hidden shadow-2xl border border-white/15 bg-gradient-to-br from-slate-950 via-sky-950 to-violet-950 text-white group"
        >
            {{-- Admin-managed banner images. Keep the gradient as a fallback
                 when an older seeded record points to a missing file. --}}
            @foreach ($banners as $index => $banner)
                @if ($banner->image_path)
                    <div
                        x-show="activeSlide === {{ $index }}"
                        x-transition.opacity.duration.500ms
                        class="absolute inset-0"
                    >
                        <img
                            src="{{ asset('storage/' . $banner->image_path) }}"
                            alt="{{ $banner->title }}"
                            class="h-full w-full object-cover"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            decoding="async"
                            data-img-fallback="hide-parent"
                        />
                    </div>
                @endif
            @endforeach

            {{-- Bottom-heavy gradient so the image stays clearly visible --}}
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent z-[1]"></div>

            {{-- Banner Content (bottom-aligned, responsive) --}}
            <div class="relative z-10 min-h-[200px] sm:min-h-[240px] lg:min-h-[280px] flex flex-col justify-end p-3 sm:p-5 lg:p-6">
                <div class="grid">
                    @foreach ($banners as $index => $banner)
                        <div
                            :class="activeSlide === {{ $index }}
                                ? 'opacity-100 translate-y-0 pointer-events-auto'
                                : 'opacity-0 translate-y-1 pointer-events-none'"
                            :aria-hidden="activeSlide === {{ $index }} ? 'false' : 'true'"
                            class="max-w-2xl col-start-1 row-start-1 transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)]"
                        >
                            <div class="hidden sm:inline-flex items-center space-x-2 px-3 py-0.5 mb-2 rounded-full text-[10px] font-black bg-sky-400/25 text-sky-200 border border-sky-400/30">
                                <span>⚡ {{ __('messages.special_offer') }} #{{ $index + 1 }}</span>
                            </div>

                            <h2 class="text-base sm:text-2xl lg:text-3xl font-black font-outfit leading-snug tracking-tight text-white drop-shadow-lg line-clamp-2 sm:line-clamp-1">
                                {{ $banner->title }}
                            </h2>

                            <p class="text-[11px] sm:text-sm text-slate-100 font-myanmar leading-relaxed drop-shadow-sm line-clamp-2 mt-1">
                                {{ $banner->description ?: __('messages.banner_fallback_caption', ['store' => $storeDisplayName]) }}
                            </p>

                            @if ($banner->link_url)
                                <a href="{{ $banner->link_url }}" class="inline-flex items-center space-x-1.5 mt-2 px-3 py-1.5 sm:px-4 sm:py-2 min-h-[36px] sm:min-h-[40px] bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 hover:from-violet-600 hover:to-violet-500 text-white font-extrabold text-[11px] sm:text-xs rounded-lg shadow-lg shadow-sky-500/30 transition active:scale-95">
                                    <span>{{ __('messages.view_detail') }}</span>
                                    <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Slider Controls & Indicator Dots --}}
                @if ($banners->count() > 1)
                    <div class="flex items-center justify-between mt-2 pt-2 border-t border-white/10 sm:mt-3">
                        <div class="flex items-center space-x-1.5">
                            @foreach ($banners as $index => $banner)
                                <button
                                    @click="activeSlide = {{ $index }}"
                                    class="h-1.5 sm:h-2 rounded-full transition-all duration-500 ease-out focus:outline-none"
                                    :class="activeSlide === {{ $index }} ? 'w-5 sm:w-7 bg-sky-400 shadow-md shadow-sky-400/50' : 'w-1.5 sm:w-2 bg-white/30 hover:bg-white/60'"
                                    title="Slide {{ $index + 1 }}"
                                ></button>
                            @endforeach
                        </div>

                        <div class="flex items-center space-x-1.5">
                            <button @click="prev()" class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 flex items-center justify-center text-white transition active:scale-90 text-xs" title="Previous Slide">&larr;</button>
                            <button @click="next()" class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-white/10 hover:bg-white/20 border border-white/20 flex items-center justify-center text-white transition active:scale-90 text-xs" title="Next Slide">&rarr;</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Default Conversion Hero Banner --}}
        <div class="relative overflow-hidden p-6 sm:p-10 shadow-2xl border border-slate-200 dark:border-slate-800/80 bg-gradient-to-br from-sky-500/10 via-white/90 to-rose-500/10 dark:from-slate-950 dark:via-sky-950 dark:to-violet-950">
            {{-- Glowing Ambient Light --}}
            <div class="absolute -top-24 -right-24 w-80 h-80 bg-sky-500/15 dark:bg-sky-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-80 h-80 bg-purple-500/15 dark:bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="max-w-2xl space-y-5 relative z-10">
                <div class="inline-flex items-center space-x-2 px-3.5 py-1 rounded-full text-xs font-extrabold bg-sky-100 dark:bg-sky-500/25 text-sky-800 dark:text-sky-200 border border-sky-300 dark:border-sky-400/30 shadow-sm">
                    <span>{{ __('messages.nationwide_shipping') }}</span>
                </div>
                <h1 class="text-2xl sm:text-4xl font-black font-outfit leading-tight tracking-tight text-slate-900 dark:text-white">
                    {{ $storeDisplayName }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-200 font-myanmar leading-relaxed font-medium">
                    {{ __('messages.hero_description') }}
                </p>
                <div class="flex flex-wrap gap-3 pt-2">
                    <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="min-h-[44px] px-6 py-3 bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 hover:from-violet-600 hover:via-violet-500 hover:to-rose-500 text-white font-extrabold text-xs sm:text-sm rounded-xl shadow-xl shadow-sky-500/25 transition flex items-center space-x-2 active:scale-95">
                        <span>{{ __('messages.view_products') }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                    <a href="{{ url('/glass-finder?store_slug=' . $storeSlug) }}" class="min-h-[44px] px-6 py-3 bg-white/80 dark:bg-white/10 hover:bg-slate-100 dark:hover:bg-white/20 text-slate-800 dark:text-white font-extrabold text-xs sm:text-sm rounded-xl border border-slate-300 dark:border-white/20 transition flex items-center space-x-2 active:scale-95 shadow-sm">
                        <span>{{ __('messages.glass_finder') }}</span>
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Below-banner sections keep tight responsive spacing --}}
    <div class="space-y-1 sm:space-y-1.5 lg:space-y-2">
    {{-- 2. Flash Sale — limited-time deals with live countdown (active + "starting soon") --}}
    @if (($flashSales->count() + $upcomingSales->count()) > 0)
        @php
            // Order: time-windowed active deals first, then scheduled ones,
            // then evergreen discounts — capped at 10 cards.
            $activeWindowed = $flashSales->filter(fn ($p) => $p->sale_ends_at !== null);
            $evergreen = $flashSales->filter(fn ($p) => $p->sale_ends_at === null);
            $allDeals = $activeWindowed->concat($upcomingSales)->concat($evergreen)->take(10);
            $maxDealPercent = $allDeals->reduce(function (int $carry, $p): int {
                if ($p->old_price !== null && (float) $p->old_price > (float) $p->retail_price) {
                    $percent = (int) round((1 - (float) $p->retail_price / (float) $p->old_price) * 100);
                    return max($carry, $percent);
                }
                return $carry;
            }, 0);
        @endphp
        <div id="flash-sale-section" class="relative overflow-hidden rounded-2xl p-3 sm:p-5 border border-rose-500/40 bg-gradient-to-br from-rose-500/10 via-fuchsia-500/5 to-violet-500/10 dark:from-rose-500/15 dark:via-fuchsia-500/10 dark:to-violet-500/15 shadow-xl">
            <div class="absolute -top-16 -right-16 h-40 w-40 rounded-full bg-rose-500/15 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-16 -left-16 h-40 w-40 rounded-full bg-amber-500/15 blur-3xl pointer-events-none"></div>
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white font-outfit flex items-center gap-2">
                        <span class="relative inline-flex h-9 w-9 sm:h-10 sm:w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-rose-500 via-orange-500 to-amber-500 text-white shadow-lg shadow-rose-500/40 text-lg">
                            🔥
                            <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                            </span>
                        </span>
                        <span>{{ __('messages.flash_sale') }}</span>
                    </h2>
                    @if ($maxDealPercent > 0)
                        <span class="hidden sm:inline-flex items-center px-2 py-1 rounded-full bg-gradient-to-r from-rose-600 to-orange-500 text-white text-[11px] sm:text-xs font-black shadow-md shadow-rose-500/30">
                            -{{ $maxDealPercent }}%
                        </span>
                    @endif
                </div>

                @if ($flashTarget)
                    <div
                        x-data="flashTimer({{ $flashTarget->timestamp * 1000 }})"
                        class="flex items-center gap-1.5 sm:gap-2 bg-white dark:bg-slate-900 rounded-xl px-2 py-1.5 border border-rose-500/20 shadow-md shadow-rose-500/5"
                    >
                        <span class="text-xs sm:text-xs font-extrabold uppercase tracking-wider px-2 py-1 rounded-lg bg-white dark:bg-slate-900 text-rose-600 dark:text-rose-300 border border-rose-500/30 whitespace-nowrap">
                            {{ $flashTargetStarts ? __('messages.starting_soon') : __('messages.sale_ends_in') }}
                        </span>
                        <template x-if="!expired">
                            <span class="flex items-center gap-1 font-mono font-black tabular-nums">
                                <template x-if="days > 0">
                                    <span>
                                        <span class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-lg px-1.5 py-1 text-sm sm:text-base" x-text="String(days).padStart(2,'0')"></span>
                                        <span class="text-xs text-slate-500 dark:text-slate-600 font-extrabold mx-0.5">{{ __('messages.days_short') }}</span>
                                    </span>
                                </template>
                                <span class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-lg px-1.5 py-1 text-sm sm:text-base" x-text="String(hours).padStart(2,'0')"></span>
                                <span class="text-slate-600 dark:text-slate-500 font-black">:</span>
                                <span class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-lg px-1.5 py-1 text-sm sm:text-base" x-text="String(minutes).padStart(2,'0')"></span>
                                <span class="text-slate-600 dark:text-slate-500 font-black">:</span>
                                <span class="bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-lg px-1.5 py-1 text-sm sm:text-base" x-text="String(seconds).padStart(2,'0')"></span>
                            </span>
                        </template>
                        <span x-show="expired" x-cloak class="text-xs font-black text-slate-500 dark:text-slate-600">00 : 00 : 00</span>
                    </div>
                @endif
            </div>

            <div
                x-data="{ isDown: false, startX: 0, scrollLeft: 0 }"
                @mousedown="isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft"
                @mouseleave="isDown = false"
                @mouseup="isDown = false"
                @mousemove="if(isDown){$event.preventDefault();const x=$event.pageX-$el.offsetLeft;const walk=(x-startX)*1.5;$el.scrollLeft=scrollLeft-walk}"
                class="mt-3 flex overflow-x-auto gap-1.5 pb-1.5 pt-0.5 scrollbar-none cursor-grab active:cursor-grabbing select-none"
            >
                @foreach ($allDeals as $deal)
                    @php
                        $isUpcoming = $upcomingSales->contains('id', $deal->id);
                        $dealDefaultVariant = $deal->defaultVariant();
                        $dealImage = $dealDefaultVariant?->image_path ?: $deal->image_path;
                        // Active OR scheduled deals carry the badge (model discountPercent() only covers active windows).
                        $dealPercent = ($deal->old_price !== null && (float) $deal->old_price > (float) $deal->retail_price)
                            ? (int) round((1 - (float) $deal->retail_price / (float) $deal->old_price) * 100)
                            : null;
                    @endphp
                    <a href="{{ url('/store/' . $storeSlug . '/product/' . $deal->slug) }}"
                       class="group shrink-0 w-36 sm:w-44 rounded-2xl border border-rose-500/25 dark:border-rose-500/30 shadow-lg hover:shadow-xl hover:shadow-rose-500/15 hover:border-rose-500/50 dark:hover:border-rose-500/60 hover:-translate-y-1.5 transition-all duration-300 overflow-hidden bg-white dark:bg-slate-900 flex flex-col">
                        <div class="relative h-28 sm:h-32 bg-gradient-to-br from-slate-100 via-rose-50 to-amber-50 dark:from-slate-800 dark:via-rose-950/20 dark:to-slate-900 overflow-hidden">
                            @if ($dealImage)
                                <img src="{{ asset('storage/' . $dealImage) }}" alt="{{ $deal->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" decoding="async">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-4xl sm:text-5xl drop-shadow-md">📦</div>
                            @endif
                            @if ($dealPercent !== null)
                                <span class="absolute top-1.5 left-1.5 px-2 py-1 rounded-lg bg-gradient-to-r from-rose-600 to-orange-500 text-white text-xs sm:text-sm font-black shadow-lg shadow-rose-500/40 ring-2 ring-white/70 dark:ring-slate-900/40 group-hover:scale-110 transition-transform duration-300 origin-top-left">
                                    -{{ $dealPercent }}%
                                </span>
                            @endif
                            @if ($isUpcoming)
                                <span class="absolute top-1.5 right-1.5 px-2 py-1 rounded-lg bg-slate-900/90 text-white text-xs font-black uppercase tracking-wide shadow-md">
                                    {{ __('messages.starting_soon_short') }}
                                </span>
                            @endif
                            <div class="absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-slate-950/45 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            @if ($dealPercent !== null && ! $isUpcoming)
                                <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-rose-600 via-orange-500 to-amber-400"></div>
                            @endif
                        </div>
                        <div class="p-2 sm:p-2.5 flex-1 flex flex-col">
                            <h3 class="text-xs sm:text-xs font-extrabold text-slate-900 dark:text-white leading-snug line-clamp-2 min-h-[2.2em] group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">{{ $deal->name }}</h3>
                            <div class="mt-auto pt-1.5">
                                <div class="flex items-center justify-between gap-1">
                                    <div class="flex flex-col gap-0.5 min-w-0">
                                        @if ($deal->old_price)
                                            <span class="text-xs sm:text-xs font-bold text-slate-600 dark:text-slate-500 line-through decoration-rose-500 decoration-2 truncate">Ks {{ number_format($deal->old_price) }}</span>
                                        @endif
                                        <span class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400 truncate">Ks {{ number_format($deal->retail_price) }}</span>
                                    </div>
                                    <span class="shrink-0 inline-flex items-center justify-center w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-gradient-to-tr from-rose-600 via-fuchsia-500 to-amber-500 text-white shadow-md shadow-rose-500/30 opacity-80 group-hover:opacity-100 group-hover:scale-110 transition-all duration-300" aria-hidden="true">
                                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </span>
                                </div>
                                @if ($dealPercent !== null && $deal->old_price)
                                    <div class="mt-1.5 inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-emerald-100/80 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-300/60 dark:border-emerald-800/60">
                                        <span class="text-[10px] sm:text-[10px] font-extrabold uppercase tracking-wide">
                                            {{ __('messages.you_save') }}
                                        </span>
                                        <span class="text-[10px] sm:text-[10px] font-black">Ks {{ number_format(max(0, $deal->old_price - $deal->retail_price)) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- 3. Most Popular Category — Linn IT Mart style category cards --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base sm:text-xl font-black text-slate-900 dark:text-white font-outfit flex items-center gap-2">
                <span class="text-sky-500">🏷️</span>
                <span>{{ __('messages.most_popular_category') }}</span>
            </h2>
            <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="text-xs font-extrabold text-sky-600 dark:text-sky-400 hover:underline whitespace-nowrap">
                {{ __('messages.view_all') }} &rarr;
            </a>
        </div>

        @php
            // Icon emoji per category name (white tile — no colored bg needed).
            $iconFor = function (string $name): string {
                $lower = strtolower($name);
                if (str_contains($lower, 'ဖုန်း') || str_contains($lower, 'phone') || str_contains($lower, 'mobile'))  return '📱';
                if (str_contains($lower, 'ကွန်ပြူတာ') || str_contains($lower, 'computer') || str_contains($lower, 'laptop') || str_contains($lower, 'macbook')) return '💻';
                if (str_contains($lower, 'glass') || str_contains($lower, 'မှန်'))                                 return '🛡️';
                if (str_contains($lower, 'charger') || str_contains($lower, 'cable') || str_contains($lower, 'အားသွင်း')) return '🔌';
                if (str_contains($lower, 'tool') || str_contains($lower, 'service') || str_contains($lower, 'ပြင်ဆင်'))  return '🛠️';
                if (str_contains($lower, 'case') || str_contains($lower, 'cover') || str_contains($lower, 'အိတ်'))       return '🖼️';
                if (str_contains($lower, 'camera') || str_contains($lower, 'cctv') || str_contains($lower, 'lens'))       return '📷';
                if (str_contains($lower, 'spare part') || str_contains($lower, 'အပိုပစ္စည်း'))                          return '🔧';
                return '📦';
            };
        @endphp

        {{-- Single-row horizontal scroll — Main category cards (most popular first) --}}
        <div
            x-data="{ isDown: false, startX: 0, scrollLeft: 0 }"
            @mousedown="isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft"
            @mouseleave="isDown = false"
            @mouseup="isDown = false"
            @mousemove="if(isDown){$event.preventDefault();const x=$event.pageX-$el.offsetLeft;const walk=(x-startX)*1.5;$el.scrollLeft=scrollLeft-walk}"
            class="flex overflow-x-auto gap-1.5 sm:gap-2 pb-1 pt-0.5 cursor-grab active:cursor-grabbing select-none scrollbar-none"
        >
            @forelse ($categoryTree as $mainRow)
                @php
                    $main = $mainRow->category;
                    $mainIcon = $main->icon ?: $iconFor($main->name);
                @endphp
                @php $cover = $main->image_path ?: $main->cover; @endphp
                <a href="{{ url('/products?store_slug=' . $storeSlug . '&category_id=' . $main->id) }}" class="group shrink-0 w-36 sm:w-40 flex flex-col" aria-label="{{ $main->name }}">
                    <div class="w-full aspect-square overflow-hidden rounded-2xl bg-white dark:bg-slate-800">
                        @if ($cover)
                            <img src="{{ asset('storage/' . $cover) }}" alt="{{ $main->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" decoding="async" data-img-fallback="hide-next" />
                            <span class="hidden w-full h-full items-center justify-center text-3xl sm:text-4xl">{{ $mainIcon }}</span>
                        @else
                            <div class="w-full h-full flex items-center justify-center text-3xl sm:text-4xl">{{ $mainIcon }}</div>
                        @endif
                    </div>
                    <h3 class="mt-2 text-center text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white truncate group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                        {{ $main->name }}
                    </h3>
                </a>
            @empty
                <div class="text-xs text-slate-600 dark:text-slate-500 py-4">
                    {{ __('messages.no_products_hint') }}
                </div>
            @endforelse
        </div>
    </div>

    {{-- 4. Glass Finder CTA Banner (compact) — no glass-card to avoid white background override in light mode --}}
    <div class="rounded-2xl p-4 sm:p-5 lg:p-8 border border-sky-500/40 bg-gradient-to-r from-sky-500/30 via-violet-500/30 to-rose-500/30 shadow-xl flex flex-row flex-nowrap items-center justify-between gap-3 lg:gap-6">
        <div class="flex items-center gap-3 lg:gap-4 min-w-0">
            <div class="w-10 h-10 lg:w-14 lg:h-14 rounded-xl bg-gradient-to-tr from-violet-600 via-fuchsia-500 to-rose-500 text-white flex items-center justify-center text-xl lg:text-3xl shadow-lg shadow-sky-500/30 shrink-0">
                📱
            </div>
            <div class="min-w-0">
                <div class="text-xs lg:text-xs font-extrabold uppercase tracking-wider text-sky-700 dark:text-sky-300 truncate">
                    ✨ {{ __('messages.fast_glass_match') }}
                </div>
                <h3 class="font-black text-sm sm:text-base lg:text-xl text-slate-900 dark:text-white font-outfit">
                    {{ __('messages.glass_finder') }}
                </h3>
            </div>
        </div>

        <a href="{{ url('/glass-finder?store_slug=' . $storeSlug) }}" class="shrink-0 px-4 py-2.5 lg:px-6 lg:py-3 bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 hover:from-violet-600 hover:via-violet-500 hover:to-rose-500 text-white font-extrabold text-xs lg:text-sm rounded-xl shadow-lg shadow-sky-500/25 transition active:scale-95 flex items-center gap-1.5 whitespace-nowrap">
            <span>{{ __('messages.glass_finder') }}</span>
            <svg class="w-3.5 h-3.5 lg:w-4 lg:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
    </div>

    {{-- 5. Best Seller (Featured) Products Section --}}
    @if ($featuredProducts->count() > 0)
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-outfit">
                    {{ __('messages.featured_products') }}
                </h2>
                <p class="text-xs text-slate-600 dark:text-slate-600 font-myanmar font-semibold">{{ __('messages.featured_subtitle') }}</p>
                </div>
                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="text-xs font-extrabold text-sky-600 dark:text-sky-400 hover:underline flex items-center space-x-1">
                    <span>{{ __('messages.view_all') }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            {{-- Responsive Grid: 5 cols desktop, 3 tablet, 2 mobile --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-px bg-slate-200 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                @foreach ($featuredProducts as $product)
                <x-product-card 
                    :product="$product" 
                    :store="$store" 
                    :isWholesaleApproved="$isWholesaleApproved" 
                    :dense="true"
                />
                @endforeach
            </div>
        </div>
    @endif

    {{-- 6. New Arrival Section --}}
    @if (isset($newArrivals) && $newArrivals->count() > 0)
        <div class="space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-outfit">
                        {{ __('messages.new_arrivals') }}
                    </h2>
                    <p class="text-xs text-slate-600 dark:text-slate-600 font-myanmar font-semibold">{{ __('messages.new_arrivals_subtitle') }}</p>
                </div>
                <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="text-xs font-extrabold text-sky-600 dark:text-sky-400 hover:underline flex items-center space-x-1">
                    <span>{{ __('messages.view_all') }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            {{-- Responsive Grid: 5 cols desktop, 3 tablet, 2 mobile --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-px bg-slate-200 dark:bg-slate-800 border border-slate-200 dark:border-slate-800 rounded-2xl overflow-hidden">
                @foreach ($newArrivals as $product)
                <x-product-card 
                    :product="$product" 
                    :store="$store" 
                    :isWholesaleApproved="$isWholesaleApproved" 
                    :dense="true"
                />
                @endforeach
            </div>
        </div>
    @endif
    </div>
</div>
@endsection
