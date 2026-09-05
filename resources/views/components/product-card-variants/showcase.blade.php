@props([
    'product',
    'store',
    'isWholesaleApproved' => false,
    'dense' => false,
])

{{--
  Showcase card variant (approved for midnight_tech / sunset_warm).
  Same data contract as the compact variant — larger padded image, centered
  title/price, generous whitespace, one prominent action row. No extra
  queries: everything comes from the already-loaded $product.
--}}

@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $productUrl = url("/store/{$storeSlug}/product/{$product->slug}");
    $defaultVariant = $product->defaultVariant();
    $cardImage = $defaultVariant?->image_path ?: $product->image_path;
    $retailPrice = $defaultVariant && (float) $defaultVariant->retail_price > 0 ? $defaultVariant->retail_price : $product->retail_price;
    $wholesalePrice = $defaultVariant && (float) ($defaultVariant->wholesale_price ?? 0) > 0 ? $defaultVariant->wholesale_price : $product->wholesale_price;
    $effectivePrice = $isWholesaleApproved && $wholesalePrice > 0 ? $wholesalePrice : $retailPrice;
    $effectiveSku = $defaultVariant?->sku ?: $product->sku;
    $cartName = $defaultVariant ? "{$product->name} - {$defaultVariant->name}" : $product->name;
    $cardInStock = $defaultVariant ? $defaultVariant->isInStock() : $product->isInStock();
    $showRetailSale = ! $isWholesaleApproved && $product->isOnSale();
    $allImages = $product->getAllImagePathsAttribute();
    $hoverImage = $allImages[1] ?? null;
    $cardKey = 'sc' . ($product->id ?? 'x') . '-' . \Illuminate\Support\Str::random(6);
@endphp

<div
    x-data="{ shareOpen: false, hoverImg: false, cardKey: '{{ $cardKey }}', sharePayload: {{ \Illuminate\Support\Js::from(['title' => $product->name, 'text' => $product->name . ' — ' . config('app.name', 'DataPOS'), 'url' => $productUrl]) }} }"
    @keyup.escape.window="shareOpen = false"
    class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-3 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900 sm:p-4"
>
    {{-- Padded image area (the showcase look) --}}
    <div class="relative overflow-hidden rounded-xl bg-slate-50 dark:bg-slate-800/60" @mouseenter="hoverImg = true; $refs.hoverImg && ($refs.hoverImg.src = $refs.hoverImg.dataset.src)" @mouseleave="hoverImg = false">
        <a href="{{ $productUrl }}" class="block touch-manipulation">
            <div class="transition-opacity duration-300 group-hover:opacity-0" :class="hoverImg ? 'opacity-0' : 'opacity-100'">
                <x-product-image :path="$cardImage" :alt="$product->name" class="aspect-square w-full object-contain transition-transform duration-300 group-hover:scale-105" />
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

        {{-- Favorite — 3D tactile push button top-right over the image --}}
        <button
            @click.stop.prevent="$store.favoritesStore.toggle({ id: {{ $product->id }}, name: {{ json_encode($product->name) }}, brand: {{ json_encode($product->brand?->name ?? 'General') }}, url: {{ json_encode($productUrl) }}, image_path: {{ json_encode($cardImage ?? '') }} })"
            type="button"
            style="position: absolute;"
            class="absolute right-2 top-2 z-10 w-9 h-9 rounded-full flex items-center justify-center p-0 ring-1 ring-white/70 transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0.5 select-none"
            :class="$store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }})
                ? 'bg-gradient-to-b from-rose-500 via-rose-600 to-rose-700 border border-rose-300 border-b-[3px] border-b-rose-900 shadow-md shadow-rose-950/40'
                : 'bg-gradient-to-b from-amber-300 via-amber-400 to-amber-600 border border-amber-200 border-b-[3px] border-b-amber-700 shadow-md shadow-amber-900/30'"
            :aria-label="$store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }}) ? '{{ __('messages.favorites') }}' : '{{ __('messages.favorites') }}'"
        >
            <svg class="w-4 h-4 text-white drop-shadow-sm transition-transform group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
        </button>

        {{-- Stock / sale badges --}}
        <div class="absolute left-2 top-2 z-10 flex items-center gap-1 pointer-events-none">
            @if ($cardInStock)
                <span class="rounded-full bg-emerald-500/90 px-2 py-0.5 text-[10px] font-black text-white shadow-sm">{{ __('messages.in_stock') }}</span>
            @else
                <span class="rounded-full bg-rose-500/90 px-2 py-0.5 text-[10px] font-black text-white shadow-sm">{{ __('messages.out_of_stock') }}</span>
            @endif
            @if ($showRetailSale)
                <span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-black text-white shadow-sm">-{{ $product->discountPercent() }}%</span>
            @endif
        </div>
    </div>

    {{-- Centered title + price --}}
    <div class="mt-3 flex flex-1 flex-col items-center text-center">
        <h3 class="line-clamp-2 text-sm font-extrabold leading-snug text-slate-900 dark:text-white">
            <a href="{{ $productUrl }}" class="transition-colors hover:text-sky-600 dark:hover:text-sky-400">{{ $product->name }}</a>
        </h3>

        <div class="mt-2 flex items-baseline justify-center gap-2">
            @if ($isWholesaleApproved && $wholesalePrice > 0)
                <span class="text-base font-black text-emerald-700 dark:text-emerald-400">{{ __('messages.wholesale') }}: {{ format_currency($wholesalePrice, $store) }}</span>
            @else
                @if ($showRetailSale)
                    <span class="text-xs text-slate-500 line-through decoration-rose-500 decoration-2 dark:text-slate-400">{{ format_currency($product->old_price, $store) }}</span>
                @endif
                <span class="text-lg font-black text-sky-700 dark:text-sky-400 font-outfit">{{ format_currency($effectivePrice, $store) }}</span>
            @endif
        </div>
        @if ($showRetailSale && $product->saleWindowLabel())
            <div class="mt-1 text-[11px] font-bold text-rose-600 dark:text-rose-400">{{ $product->saleWindowLabel() }}</div>
        @endif
    </div>

    {{-- One prominent action row --}}
    <div class="mt-3 flex items-center justify-center gap-2">
        @if ($cardInStock)
            <button
                @click.stop.prevent="$store.orderBuilder.addItem({ id: {{ $product->id }}, product_variant_id: {{ $defaultVariant?->id ?? 'null' }}, variant_id: {{ $defaultVariant?->id ?? 'null' }}, name: {{ json_encode($cartName) }}, price: {{ $effectivePrice }}, sku: {{ json_encode($effectiveSku ?? '') }}, image_path: {{ json_encode($cardImage ?? '') }} })"
                type="button"
                class="sf-btn-3d-primary min-h-9 flex-1 !text-xs !py-1.5 inline-flex items-center justify-center gap-1.5"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M3 3h2l.4 2M7 13h10l3-8H5.4M7 13 5.4 5M7 13l-2 5h14M9 21a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm8 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"/>
                </svg>
                {{ __('messages.add_to_order') }}
                <span x-show="$store.orderBuilder && $store.orderBuilder.getItemQty({{ $product->id }}) > 0" class="grid h-4 min-w-4 place-items-center rounded-full bg-white px-1 text-[10px] font-black text-sky-700" x-text="$store.orderBuilder ? $store.orderBuilder.getItemQty({{ $product->id }}) : 0"></span>
            </button>
        @endif

        <a href="{{ $productUrl }}"
           class="sf-btn-3d min-h-9 !text-xs !py-1.5 inline-flex items-center justify-center">
            {{ __('messages.details') }}
        </a>
    </div>
</div>
