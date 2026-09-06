@props([
    'product',
    'store',
    'isWholesaleApproved' => false,
    'dense' => false,
    'rounded' => 'rounded-2xl',
])

@once
    <style>
        @media (max-width: 639px) {
            .mobile-hide { display: none !important; }
        }
    </style>
@endonce

@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $productUrl = url("/store/{$storeSlug}/product/{$product->slug}");
    $defaultVariant = $product->defaultVariant();
    $cardImage = $defaultVariant?->image_path ?: $product->image_path;

    // A variant with a 0/empty price (e.g. a name-only variant row from an
    // import) must never display as "Ks 0" — fall back to the product price.
    $retailPrice = $defaultVariant && (float) $defaultVariant->retail_price > 0 ? $defaultVariant->retail_price : $product->retail_price;
    $wholesalePrice = $defaultVariant && (float) ($defaultVariant->wholesale_price ?? 0) > 0 ? $defaultVariant->wholesale_price : $product->wholesale_price;
    $effectivePrice = $isWholesaleApproved && $wholesalePrice > 0 ? $wholesalePrice : $retailPrice;
    $effectiveSku = $defaultVariant?->sku ?: $product->sku;
    $cartName = $defaultVariant ? "{$product->name} - {$defaultVariant->name}" : $product->name;
    $cardInStock = $defaultVariant ? $defaultVariant->isInStock() : $product->isInStock();
    $showRetailSale = ! $isWholesaleApproved && $product->isOnSale();

    // One-click share links (no backend — standard platform share URLs)
    $shareText = $product->name . ' — ' . config('app.name', 'DataPOS');
    $shareFbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($productUrl);
    $shareTgUrl = 'https://t.me/share/url?url=' . rawurlencode($productUrl) . '&text=' . rawurlencode($shareText);
    $shareViberUrl = 'viber://forward?text=' . rawurlencode($shareText . ' ' . $productUrl);
    $sharePayload = \Illuminate\Support\Js::from([
        'title' => $product->name,
        'text' => $shareText,
        'url' => $productUrl,
    ]);

    // Unique per-card key so opening one card's actions closes all others (mobile touch)
    $cardKey = 'c' . ($product->id ?? 'x') . '-' . Str::random(6);

    // Dense (glued hairline grid) cards drop the glass border/rounding/hover lift.
    $cardShell = $dense
        ? 'bg-white dark:bg-slate-900'
        : "bg-white dark:bg-slate-900 {$rounded} border border-slate-200/90 dark:border-slate-800/80 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-sky-500/10";
@endphp

<div
    x-data="{ shareOpen: false, hoverImg: false, reveal: false, cardKey: '{{ $cardKey }}', touchStartY: 0, revealAt: 0, sharePayload: {{ $sharePayload }} }"
    @keyup.escape.window="shareOpen = false"
    @card-revealed.window="if ($event.detail.key !== cardKey) reveal = false"
    class="group {{ $cardShell }} p-0 flex flex-col relative transition-all duration-300 overflow-hidden"
    @mouseenter="hoverImg = true"
    @mouseleave="hoverImg = false"
>
    {{-- Top-left badges (full-bleed image carries them) --}}
    <div class="absolute top-2.5 left-2.5 z-10 flex items-center gap-1 pointer-events-none">
        @if ($cardInStock)
            <span class="badge-discount bg-gradient-to-r from-emerald-500 to-teal-600 shadow-emerald-500/20 border-emerald-300/40">
                {{ __('messages.in_stock') }}
            </span>
        @else
            <span class="badge-discount bg-gradient-to-r from-rose-500 to-red-600 shadow-rose-500/20 border-rose-300/40">
                {{ __('messages.out_of_stock') }}
            </span>
        @endif

        @if ($product->is_featured)
            <span class="shrink-0 badge-discount bg-gradient-to-r from-amber-400 to-orange-500 shadow-amber-500/20 border-amber-300/40" aria-label="{{ __('messages.featured') }}">
                ★★★
            </span>
        @endif

        {{-- In-Cart Badge Count --}}
        <span
            x-show="$store.orderBuilder && $store.orderBuilder.getItemQty({{ $product->id }}) > 0"
            x-transition
            class="shrink-0 grid h-5 w-5 place-items-center rounded-full bg-gradient-to-br from-violet-600 to-fuchsia-500 text-xs font-black text-white shadow-md shadow-violet-500/30 border border-white/70"
            aria-label="{{ __('messages.add_to_order') }}"
        >
            <span x-text="$store.orderBuilder ? $store.orderBuilder.getItemQty({{ $product->id }}) : 0"></span>
        </span>
    </div>

    <div>
        {{-- Full-bleed image (flush edge-to-edge) + tap-to-reveal action bar --}}
        @php
            $allImages = $product->getAllImagePathsAttribute();
            $hoverImage = $allImages[1] ?? null;
        @endphp
        <div class="relative block overflow-hidden">
            <a href="{{ $productUrl }}" class="block touch-manipulation"
               @touchstart="if (window.innerWidth < 1024) { touchStartY = $event.touches[0].clientY; if (!reveal) { reveal = true; revealAt = Date.now(); window.dispatchEvent(new CustomEvent('card-revealed', { detail: { key: cardKey } })) } }"
               @touchmove="if (window.innerWidth < 1024 && Math.abs($event.touches[0].clientY - touchStartY) > 10) reveal = false"
               @click="if (window.innerWidth < 1024 && Date.now() - revealAt > 400) { reveal = !reveal; if (reveal) { revealAt = Date.now(); window.dispatchEvent(new CustomEvent('card-revealed', { detail: { key: cardKey } })) } }">
                <div class="transition-opacity duration-300 group-hover:opacity-0" :class="hoverImg ? 'opacity-0' : 'opacity-100'">
                    <x-product-image :path="$cardImage" :alt="$product->name" class="w-full h-full object-contain" aspect="aspect-square" />
                </div>
                @if ($hoverImage)
                    <div class="absolute inset-0 transition-opacity duration-300 pointer-events-none z-10 bg-slate-950 opacity-0 group-hover:opacity-100" :class="hoverImg ? 'opacity-100' : ''">
                        <img
                            x-ref="hoverImg"
                            src="{{ asset('storage/' . $hoverImage) }}?v={{ @filemtime(public_path('storage/' . $hoverImage)) ?: '2' }}"
                            alt="{{ $product->name }}"
                            loading="lazy"
                            decoding="async"
                            class="w-full h-full object-contain"
                            data-img-fallback="hide"
                        />
                    </div>
                @endif
            </a>

            {{-- Favorite — 3D tactile push button always visible at top-right corner --}}
            <button
                @click.stop.prevent="$store.favoritesStore.toggle({ id: {{ $product->id }}, name: {{ json_encode($product->name) }}, brand: {{ json_encode($product->brand?->name ?? 'General') }}, url: {{ json_encode($productUrl) }}, image_path: {{ json_encode($cardImage ?? '') }} })"
                type="button"
                style="position: absolute;"
                class="absolute top-1.5 right-1.5 sm:top-2 sm:right-2 z-20 w-7 h-7 sm:w-8 sm:h-8 rounded-full flex items-center justify-center p-0 ring-1 ring-white/70 transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0.5 select-none"
                :class="$store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }})
                    ? 'bg-gradient-to-b from-rose-500 via-rose-600 to-rose-700 border border-rose-300 border-b-[2px] border-b-rose-900 shadow-sm shadow-rose-950/40'
                    : 'bg-gradient-to-b from-amber-300 via-amber-400 to-amber-600 border border-amber-200 border-b-[2px] border-b-amber-700 shadow-sm shadow-amber-900/30'"
                title="{{ __('messages.favorites') }}"
                aria-label="{{ __('messages.favorites') }}"
            >
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white drop-shadow-sm transition-transform group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </button>

            {{-- Tap-to-reveal action bar (cart / share / details) — bottom of the image --}}
            <div
                x-cloak
                @click="if (Date.now() - revealAt > 400) reveal = false"
                class="absolute inset-x-0 bottom-0 z-10 flex items-center justify-center gap-2.5 pt-10 pb-3 bg-gradient-to-t from-slate-900/60 via-slate-900/25 to-transparent transition-opacity duration-200"
                :class="reveal ? 'opacity-100 pointer-events-auto' : 'opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto'"
            >
                @if ($cardInStock)
                    <button
                        @click.stop.prevent="if (Date.now() - revealAt > 400) $store.orderBuilder.addItem({ id: {{ $product->id }}, product_variant_id: {{ $defaultVariant?->id ?? 'null' }}, variant_id: {{ $defaultVariant?->id ?? 'null' }}, name: {{ json_encode($cartName) }}, price: {{ $effectivePrice }}, sku: {{ json_encode($effectiveSku ?? '') }}, image_path: {{ json_encode($cardImage ?? '') }} })"
                        type="button"
                        class="sf-btn-3d-primary !rounded-full relative w-11 h-11 !p-0 flex items-center justify-center shadow-xl ring-1 ring-white/60"
                        title="{{ __('messages.add_to_order') }}"
                        aria-label="{{ __('messages.add_to_order') }}"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13 5.4 5M7 13l-2 5h14M9 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/>
                        </svg>
                        <span x-show="$store.orderBuilder && $store.orderBuilder.getItemQty({{ $product->id }}) > 0" class="absolute -top-1.5 -right-1.5 grid h-[18px] min-w-[18px] place-items-center rounded-full bg-white px-1 text-xs font-black text-sky-700 border-2 border-sky-500 shadow-md" x-text="$store.orderBuilder ? $store.orderBuilder.getItemQty({{ $product->id }}) : 0"></span>
                    </button>
                @endif

                <a
                    href="{{ $productUrl }}"
                    @click.prevent.stop="if (Date.now() - revealAt > 400) window.location.href = '{{ $productUrl }}'"
                    class="sf-btn-3d-success !rounded-full w-11 h-11 !p-0 flex items-center justify-center shadow-xl ring-1 ring-white/60"
                    title="{{ __('messages.details') }}"
                    aria-label="{{ __('messages.details') }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </a>

                {{-- One-click Share dropdown (FB / Telegram / Viber + native Web Share) --}}
                <div class="relative" data-card-share>
                    <button
                        type="button"
                        @click.stop.prevent="if (Date.now() - revealAt > 400) shareOpen = !shareOpen"
                        @click.outside="shareOpen = false"
                        aria-haspopup="true"
                        :aria-expanded="shareOpen ? 'true' : 'false'"
                        class="sf-btn-3d-accent !rounded-full w-11 h-11 !p-0 flex items-center justify-center shadow-xl ring-1 ring-white/60"
                        title="{{ __('messages.share') }}"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.032 4.026a3 3 0 10-2.684-4.026m2.684 4.026a3 3 0 012.684 0M9 13.342l6.316 3.684M15.316 9.658L9 13.342"/>
                        </svg>
                    </button>

                    <div
                        x-show="shareOpen"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 bottom-full mb-2 z-50 w-44 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xl py-1 text-slate-700 dark:text-slate-200"
                        role="menu"
                    >
                        {{-- Native Web Share API (Android/iOS share sheet) — shown only when supported --}}
                        <button
                            type="button"
                            x-data="{ canShare: false }"
                            x-init="canShare = typeof navigator !== 'undefined' && typeof navigator.share === 'function'"
                            x-show="canShare"
                            @click.stop.prevent="navigator.share(sharePayload).finally(() => shareOpen = false)"
                            class="w-full flex items-center gap-2 px-3 py-2 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-700 text-left"
                            role="menuitem"
                        >
                            <span>📱</span> <span>{{ __('messages.share_via_app') }}</span>
                        </button>

                        <a href="{{ $shareFbUrl }}" target="_blank" rel="noopener noreferrer" @click="shareOpen = false"
                           class="w-full flex items-center gap-2 px-3 py-2 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-700" role="menuitem">
                            <x-brand-icon brand="facebook" class="h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400"/> <span>{{ __('messages.facebook') }}</span>
                        </a>
                        <a href="{{ $shareTgUrl }}" target="_blank" rel="noopener noreferrer" @click="shareOpen = false"
                           class="w-full flex items-center gap-2 px-3 py-2 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-700" role="menuitem">
                            <x-brand-icon brand="telegram" class="h-4 w-4 shrink-0 text-sky-500 dark:text-sky-400"/> <span>Telegram</span>
                        </a>
                        <a href="{{ $shareViberUrl }}" @click="shareOpen = false"
                           class="w-full flex items-center gap-2 px-3 py-2 text-xs font-bold hover:bg-slate-100 dark:hover:bg-slate-700" role="menuitem">
                            <x-brand-icon brand="viber" class="h-4 w-4 shrink-0 text-violet-600 dark:text-violet-400"/> <span>{{ __('messages.share_via_viber') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Product title (+ warranty on desktop only; SKU lives on the product detail page) --}}
        <div class="flex flex-row items-center gap-1.5 text-xs px-2.5 pt-2" data-card-title-row>
            <h3 class="flex-1 min-w-0 font-extrabold text-sm text-slate-900 dark:text-white truncate leading-snug font-sans group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
                <a href="{{ $productUrl }}">{{ $product->name }}</a>
            </h3>

            @if (! empty($product->warranty))
                <span class="shrink-0 inline-flex items-center justify-center text-blue-800 dark:text-blue-400 mobile-hide" title="{{ $product->warranty }}" aria-label="{{ __('messages.warranty') . ': ' . $product->warranty }}">
                    <svg class="w-4 h-4 sm:w-[18px] sm:h-[18px]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2L4 5v6c0 5.5 3.8 10.7 8 12 4.2-1.3 8-6.5 8-12V5l-8-3z"/></svg>
                </span>
            @endif
        </div>
    </div>

    {{-- Pricing (compact — stays close under the title) --}}
    <div class="px-2.5 pb-2.5 mt-1.5">
        <div class="flex flex-col">
            @if ($isWholesaleApproved && $wholesalePrice > 0)
                <div class="flex items-baseline justify-center gap-1.5 min-w-0">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 font-myanmar">{{ __('messages.wholesale') }}:</span>
                    <span class="text-sm sm:text-base font-black text-emerald-700 dark:text-emerald-400 font-outfit">
                        {{ format_currency($wholesalePrice, $store) }}
                    </span>
                </div>
            @else
                {{-- Price: single row for label and amount --}}
                <div class="flex items-baseline justify-center flex-wrap gap-x-1.5 gap-y-0.5 text-center">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 font-myanmar shrink-0">{{ __('messages.price') }}:</span>
                    <span class="text-sm sm:text-base font-black text-sky-700 dark:text-sky-400 font-outfit leading-tight">
                        {{ format_currency($retailPrice, $store) }}
                    </span>
                    @if ($showRetailSale)
                        <div class="flex items-center gap-1 shrink-0">
                            <span class="text-[11px] text-slate-400 dark:text-slate-500 line-through decoration-rose-500 decoration-1.5">
                                {{ format_currency($product->old_price, $store) }}
                            </span>
                            <span class="px-1 py-0.2 rounded text-[10px] font-black bg-rose-500 text-white shadow-2xs">
                                -{{ $product->discountPercent() }}%
                            </span>
                        </div>
                    @endif
                </div>
                @if ($showRetailSale && $product->saleWindowLabel())
                    <div class="mt-1 text-[11px] font-bold text-rose-600 dark:text-rose-400 text-center">
                        {{ $product->saleWindowLabel() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

</div>
