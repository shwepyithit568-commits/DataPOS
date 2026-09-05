@props([
    'product',
    'store',
    'isWholesaleApproved' => false,
])

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
    $cartName = $defaultVariant ? "{$product->name} - {$defaultVariant->name}" : $product->name;
    $effectiveSku = $defaultVariant?->sku ?: $product->sku;
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

@endphp

<div
    x-data="{ shareOpen: false, sharePayload: {{ $sharePayload }} }"
    @keyup.escape.window="shareOpen = false"
    class="group bg-white dark:bg-slate-900 flex gap-3 p-3 relative transition"
>
    {{-- Left: full-bleed square image (flush to the row edges) --}}
    <div class="relative w-28 h-28 shrink-0 overflow-hidden rounded-xl">
        <a href="{{ $productUrl }}" @click.stop>
            <x-product-image :path="$cardImage" :alt="$product->name" class="w-full h-full object-contain" aspect="aspect-square" />
        </a>
        @if ($cardInStock)
            <span class="absolute top-1.5 left-1.5 badge-discount bg-gradient-to-r from-emerald-500 to-teal-600 shadow-emerald-500/20 border-emerald-300/40">
                {{ __('messages.in_stock') }}
            </span>
        @else
            <span class="absolute top-1.5 left-1.5 badge-discount bg-gradient-to-r from-rose-500 to-red-600 shadow-rose-500/20 border-rose-300/40">
                {{ __('messages.out_of_stock') }}
            </span>
        @endif
    </div>

    {{-- Right: name + price + tap-to-reveal actions --}}
    <div class="flex-1 min-w-0 flex flex-col justify-center">
        <h3 class="text-sm font-extrabold text-slate-900 dark:text-white leading-snug line-clamp-2 font-sans group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">
            <a href="{{ $productUrl }}">{{ $product->name }}</a>
        </h3>

        <div class="mt-1.5 flex items-baseline flex-wrap gap-x-1.5 gap-y-0.5">
            @if ($isWholesaleApproved && $wholesalePrice > 0)
                <span class="text-sm font-black text-emerald-700 dark:text-emerald-400">
                    {{ __('messages.wholesale') }}: {{ format_currency($wholesalePrice, $store) }}
                </span>
            @else
                @if ($showRetailSale && $product->old_price)
                    <span class="text-xs text-slate-600 dark:text-slate-500 line-through decoration-rose-500 decoration-2">
                        {{ format_currency($product->old_price, $store) }}
                    </span>
                    <span class="px-1.5 py-0.5 rounded-md text-xs font-black bg-rose-500 text-white shadow-sm shadow-rose-500/40">
                        -{{ $product->discountPercent() }}%
                    </span>
                @endif
                <span class="text-base font-black text-sky-700 dark:text-sky-400 font-outfit leading-tight">
                    {{ format_currency($retailPrice, $store) }}
                </span>
            @endif
        </div>

        {{-- Tap-to-reveal actions (cart / favorite / share / details) — cart is hidden for out-of-stock items (same rule as the grid card) --}}
        <div class="flex items-center gap-2 mt-2.5">
            @if ($cardInStock)
                <button
                    @click.stop.prevent="if ($store.orderBuilder) $store.orderBuilder.addItem({ id: {{ $product->id }}, product_variant_id: {{ $defaultVariant?->id ?? 'null' }}, variant_id: {{ $defaultVariant?->id ?? 'null' }}, name: {{ json_encode($cartName) }}, price: {{ $effectivePrice }}, sku: {{ json_encode($effectiveSku ?? '') }}, image_path: {{ json_encode($cardImage ?? '') }} })"
                    type="button"
                    class="sf-btn-3d-primary !rounded-full shrink-0 w-11 h-11 !p-0 flex items-center justify-center shadow-md ring-1 ring-white/60"
                    title="{{ __('messages.add_to_order') }}"
                    aria-label="{{ __('messages.add_to_order') }}"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13 5.4 5M7 13l-2 5h14M9 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/>
                    </svg>
                </button>
            @endif
            <button
                @click.stop.prevent="if ($store.favoritesStore) $store.favoritesStore.toggle({ id: {{ $product->id }}, name: {{ json_encode($product->name) }}, brand: {{ json_encode($product->brand?->name ?? 'General') }}, url: {{ json_encode($productUrl) }}, image_path: {{ json_encode($cardImage ?? '') }} })"
                type="button"
                class="shrink-0 w-11 h-11 rounded-full flex items-center justify-center p-0 ring-1 ring-white/70 transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0.5 select-none"
                :class="$store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }})
                    ? 'bg-gradient-to-b from-rose-500 via-rose-600 to-rose-700 border border-rose-300 border-b-[3px] border-b-rose-900 shadow-md shadow-rose-950/40'
                    : 'bg-gradient-to-b from-amber-300 via-amber-400 to-amber-600 border border-amber-200 border-b-[3px] border-b-amber-700 shadow-md shadow-amber-900/30'"
                title="{{ __('messages.favorites') }}"
                aria-label="{{ __('messages.favorites') }}"
            >
                <svg class="w-4 h-4 text-white drop-shadow-sm transition-transform group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </button>
            <button
                type="button"
                @click.stop.prevent="shareOpen = !shareOpen"
                class="sf-btn-3d-accent !rounded-full shrink-0 w-11 h-11 !p-0 flex items-center justify-center shadow-md ring-1 ring-white/60"
                title="{{ __('messages.share') }}"
                aria-label="{{ __('messages.share') }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.032 4.026a3 3 0 10-2.684-4.026m2.684 4.026a3 3 0 012.684 0M9 13.342l6.316 3.684M15.316 9.658L9 13.342"/>
                </svg>
            </button>
            <a
                href="{{ $productUrl }}"
                @click.stop
                class="sf-btn-3d-success !rounded-full shrink-0 w-11 h-11 !p-0 flex items-center justify-center shadow-md ring-1 ring-white/60"
                title="{{ __('messages.details') }}"
                aria-label="{{ __('messages.details') }}"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </a>

            {{-- Share dropdown (native Web Share + FB / Telegram / Viber) --}}
            <div x-show="shareOpen" x-cloak x-transition class="absolute right-3 bottom-full mb-2 z-30 w-44 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-xl py-1 text-slate-700 dark:text-slate-200" role="menu" @click.stop>
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
