@extends('layouts.storefront.app')

@section('main_padding', 'px-0.5 sm:px-3 lg:px-6 py-1 sm:py-3')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $storeSetting = $store?->setting;
    $storeLogo = $storeSetting?->adminLogo();
    $storeLogoUrl = $storeLogo ? asset('storage/' . $storeLogo) : null;
    $accountUrl = $storeSlug ? url('/account?store_slug=' . $storeSlug) : url('/account');
    $productsUrl = $storeSlug ? url('/products?store_slug=' . $storeSlug) : url('/products');
    $glassFinderUrl = $storeSlug ? url('/glass-finder?store_slug=' . $storeSlug) : url('/glass-finder');
    $orderBuilderUrl = $storeSlug ? url('/order-builder?store_slug=' . $storeSlug) : url('/order-builder');

    $hasCloudFavorites = isset($favorites) && $favorites->count() > 0;
@endphp

<div class="max-w-6xl mx-auto space-y-1.5 sm:space-y-3 select-none font-sans pb-16"
     x-data="{
         brandHue(str) {
             if (!str) return 0;
             let hue = 0;
             for (let i = 0; i < str.length; i++) {
                 hue += (str.charCodeAt(i) * (i + 3)) % 360;
             }
             return hue % 360;
         }
     }">

    {{-- 1. Top Breadcrumb & Store Header Bar --}}
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
                    <span class="truncate">{{ __('messages.favorites_title') }}</span>
                    <span class="sf-btn-3d active inline-flex items-center px-1.5 py-0.2 rounded text-[10px] sm:text-[11px] font-black pointer-events-none whitespace-nowrap"
                          x-text="($store.favoritesStore ? $store.favoritesStore.count : {{ $favorites->count() ?? 0 }}) + ' ' + '{{ __('messages.order_items_unit') }}'">
                    </span>
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium truncate">
                    {{ __('messages.favorites_subtitle') }}
                </p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-1.5 shrink-0 self-start sm:self-auto flex-wrap">
            <a href="{{ $orderBuilderUrl }}"
               class="sf-btn-3d-gold !inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-black shadow-2xs cursor-pointer"
               title="{{ __('messages.nav_cart') }}">
                <span aria-hidden="true">🛒</span>
                <span>{{ __('messages.nav_cart') }}</span>
                <span x-show="$store.orderBuilder && $store.orderBuilder.totalCount > 0"
                      class="px-1.5 py-0.2 rounded-full bg-white text-orange-600 font-black text-[10px]"
                      x-text="$store.orderBuilder ? $store.orderBuilder.totalCount : 0"></span>
            </a>

            <a href="{{ auth()->check() ? $accountUrl : url('/') }}"
               class="sf-btn-3d !inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-bold"
               title="{{ __('messages.account') }}">
                <span aria-hidden="true">←</span>
                <span>{{ auth()->check() ? __('messages.account') : __('messages.home') }}</span>
            </a>
        </div>
    </div>

    {{-- 2. Main Wishlist Container --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg sm:rounded-xl p-2.5 sm:p-4 border border-slate-200/90 dark:border-slate-800 shadow-xs space-y-3 sm:space-y-4">
        
        {{-- Section Subheader Bar --}}
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2 flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-500 flex items-center justify-center text-sm shadow-2xs">
                    ❤️
                </span>
                <div>
                    <h2 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white">
                        {{ __('messages.favorites') }}
                    </h2>
                    <p class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                        {{ __('messages.favorites_cloud_subtitle') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                <a href="{{ $productsUrl }}"
                   class="sf-btn-3d !inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] sm:text-xs font-bold">
                    <span>🛍️</span>
                    <span>{{ __('messages.favorites_browse_more') }}</span>
                </a>
            </div>
        </div>

        {{-- 3. Authenticated Cloud Favorites Section --}}
        @auth
            @if ($hasCloudFavorites)
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1">
                            <span>☁️</span>
                            <span>{{ __('messages.favorites_cloud_title') }}</span>
                        </span>
                        <span class="text-[10px] font-mono text-slate-400">
                            {{ $favorites->total() }} {{ __('messages.order_items_unit') }}
                        </span>
                    </div>

                    {{-- Compact Product Grid (2 cols on mobile, 3 on sm, 4 on lg) --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-1.5 sm:gap-2.5">
                        @foreach ($favorites as $favorite)
                            @if ($favorite->glassItem)
                                @php
                                    $glass = $favorite->glassItem;
                                    $isInStock = $glass->isInStock();
                                    $_brandStr = (string) ($glass->brand ?? 'G');
                                    $_hue = 0;
                                    for ($_i = 0; $_i < strlen($_brandStr); $_i++) {
                                        $_hue += (ord($_brandStr[$_i]) * ($_i + 3)) % 360;
                                    }
                                    $cloudHue = $_hue % 360;
                                @endphp
                                <div class="cloud-fav-row bg-slate-50/70 dark:bg-slate-800/60 rounded-lg sm:rounded-xl border border-slate-200/90 dark:border-slate-700/80 p-2 sm:p-2.5 flex flex-col gap-1.5 overflow-hidden shadow-2xs hover:shadow-xs transition relative">
                                    
                                    {{-- Full-bleed Image / Brand Fallback Tile --}}
                                    <div class="relative -mx-2 -mt-2 sm:-mx-2.5 sm:-mt-2.5 aspect-square overflow-hidden bg-slate-100 dark:bg-slate-800 rounded-t-lg sm:rounded-t-xl flex items-center justify-center">
                                        <div class="absolute inset-0 flex items-center justify-center cloud-hue-bg"
                                             style="--cloud-hue: {{ $cloudHue }}; background: hsl({{ $cloudHue }}, 65%, 94%);">
                                            <div class="p-2 sm:p-2.5 rounded-full bg-white/80 dark:bg-slate-900/60 backdrop-blur-xs shadow-2xs">
                                                <span class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white">{{ strtoupper(mb_substr($glass->brand ?? 'G', 0, 1)) }}</span>
                                            </div>
                                        </div>

                                        {{-- Stock Availability Badge (Top-Left) --}}
                                        <div class="absolute top-1.5 left-1.5">
                                            @if ($isInStock)
                                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] font-black bg-emerald-600/90 text-white backdrop-blur-xs shadow-2xs">
                                                    <span>●</span>
                                                    <span>{{ __('messages.in_stock') }}</span>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] font-black bg-rose-600/90 text-white backdrop-blur-xs shadow-2xs">
                                                    <span>●</span>
                                                    <span>{{ __('messages.out_of_stock') }}</span>
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Remove 3D Button (Top-Right) --}}
                                        <div class="absolute top-1.5 right-1.5">
                                            <button type="button"
                                                    @click.prevent="$store.favoritesStore.removeServerItem({{ $glass->id }}, $el)"
                                                    class="sf-btn-3d-danger !p-1 sm:!p-1.5 rounded-md text-xs shadow-2xs cursor-pointer select-none"
                                                    title="{{ __('messages.remove') }}">
                                                <span>🗑️</span>
                                            </button>
                                        </div>

                                        {{-- Glass Code Pill (Bottom-Right) --}}
                                        <span class="absolute bottom-1.5 right-1.5 px-1.5 py-0.5 rounded bg-slate-950/70 text-white text-[9px] font-mono font-bold backdrop-blur-xs shadow-2xs">
                                            {{ $glass->glass_code }}
                                        </span>
                                    </div>

                                    {{-- Brand & Model Info --}}
                                    <div class="min-w-0 space-y-0.5">
                                        <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-sky-100 dark:bg-sky-950 text-sky-800 dark:text-sky-300 uppercase">
                                            {{ $glass->brand }}
                                        </span>
                                        <h4 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white truncate"
                                            title="{{ $glass->phone_model }}">
                                            {{ $glass->phone_model }}
                                        </h4>
                                        <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400">
                                            Code: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $glass->glass_code }}</span>
                                        </div>
                                    </div>

                                    {{-- Bottom Actions Row --}}
                                    <div class="flex items-center gap-1 mt-auto pt-1.5 border-t border-slate-200/80 dark:border-slate-700/80">
                                        {{-- Move to Cart 3D Button --}}
                                        <button type="button"
                                                @click.stop.prevent="$store.orderBuilder.addItem({ glass_finder_item_id: {{ $glass->id }}, name: 'Glass: {{ addslashes($glass->phone_model) }} ({{ $glass->glass_code }})', price: 0, sku: {{ json_encode($glass->glass_code) }} })"
                                                class="sf-btn-3d-primary flex-1 py-1 sm:py-1.5 rounded-md text-xs font-black flex items-center justify-center gap-1 cursor-pointer select-none shadow-2xs"
                                                title="{{ __('messages.favorites_move_to_cart') }}">
                                            <span class="text-xs">🛒</span>
                                            <span>+ Cart</span>
                                            <span x-show="$store.orderBuilder && $store.orderBuilder.getGlassItemQty({{ $glass->id }}) > 0"
                                                  class="px-1.5 py-0.2 rounded-full bg-white text-orange-600 font-black text-[10px]"
                                                  x-text="$store.orderBuilder.getGlassItemQty({{ $glass->id }})"></span>
                                        </button>

                                        {{-- Finder Link 3D Button --}}
                                        <a href="{{ url('/glass-finder?phone_model=' . urlencode($glass->phone_model)) }}"
                                           class="sf-btn-3d px-2 py-1 sm:py-1.5 rounded-md text-xs font-bold"
                                           title="{{ __('messages.favorites_finder_link') }}">
                                            🔍
                                        </a>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if ($favorites->hasPages())
                        <div class="pt-2">
                            {{ $favorites->links() }}
                        </div>
                    @endif
                </div>
            @endif
        @endauth

        {{-- 4. LocalStorage / Client Favorites Section --}}
        <div x-show="$store.favoritesStore && $store.favoritesStore.items.length > 0" class="space-y-2 pt-2">
            @auth
                @if ($hasCloudFavorites)
                    <div class="flex items-center justify-between border-t border-slate-100 dark:border-slate-800 pt-3">
                        <span class="text-[11px] font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1">
                            <span>📱</span>
                            <span>Browser Local Favorites</span>
                        </span>
                        <span class="text-[10px] font-mono text-slate-400"
                              x-text="$store.favoritesStore.items.length + ' items'"></span>
                    </div>
                @endif
            @endauth

            {{-- Compact Client Favorites Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-1.5 sm:gap-2.5">
                <template x-for="item in ($store.favoritesStore ? $store.favoritesStore.items : [])" :key="item.id">
                    <div class="bg-slate-50/70 dark:bg-slate-800/60 rounded-lg sm:rounded-xl border border-slate-200/90 dark:border-slate-700/80 p-2 sm:p-2.5 flex flex-col gap-1.5 overflow-hidden shadow-2xs hover:shadow-xs transition relative"
                         x-data="{ h: brandHue(item.brand) }">
                        
                        {{-- Image / Fallback Container --}}
                        <div class="relative -mx-2 -mt-2 sm:-mx-2.5 sm:-mt-2.5 aspect-square overflow-hidden bg-slate-100 dark:bg-slate-800 rounded-t-lg sm:rounded-t-xl flex items-center justify-center">
                            {{-- Product Image if available --}}
                            <template x-if="item.image_path">
                                <img :src="'/storage/' + item.image_path"
                                     :alt="item.name"
                                     loading="lazy"
                                     decoding="async"
                                     class="w-full h-full object-cover pointer-events-none" />
                            </template>

                            {{-- Brand Fallback Tile if no image --}}
                            <template x-if="!item.image_path">
                                <div class="absolute inset-0 flex items-center justify-center cloud-hue-bg"
                                     :style="'--cloud-hue: ' + h + '; background: hsl(' + h + ', 65%, 94%);'">
                                    <div class="p-2 sm:p-2.5 rounded-full bg-white/80 dark:bg-slate-900/60 backdrop-blur-xs shadow-2xs">
                                        <span class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white"
                                              x-text="(item.brand || 'G').charAt(0).toUpperCase()"></span>
                                    </div>
                                </div>
                            </template>

                            {{-- Stock Availability Badge (Top-Left) --}}
                            <div class="absolute top-1.5 left-1.5">
                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] font-black bg-emerald-600/90 text-white backdrop-blur-xs shadow-2xs">
                                    <span>●</span>
                                    <span>{{ __('messages.in_stock') }}</span>
                                </span>
                            </div>

                            {{-- Remove 3D Button (Top-Right) --}}
                            <div class="absolute top-1.5 right-1.5">
                                <button type="button"
                                        @click="$store.favoritesStore.removeItem(item.id)"
                                        class="sf-btn-3d-danger !p-1 sm:!p-1.5 rounded-md text-xs shadow-2xs cursor-pointer select-none"
                                        title="{{ __('messages.remove') }}">
                                    <span>🗑️</span>
                                </button>
                            </div>

                            {{-- Glass Code if available --}}
                            <template x-if="item.glass_code">
                                <span class="absolute bottom-1.5 right-1.5 px-1.5 py-0.5 rounded bg-slate-950/70 text-white text-[9px] font-mono font-bold backdrop-blur-xs shadow-2xs"
                                      x-text="item.glass_code"></span>
                            </template>
                        </div>

                        {{-- Item Info --}}
                        <div class="min-w-0 space-y-0.5">
                            <span class="inline-block px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-sky-100 dark:bg-sky-950 text-sky-800 dark:text-sky-300 uppercase truncate max-w-full"
                                  x-text="item.brand || 'General'"></span>
                            <h4 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white truncate"
                                :title="item.name"
                                x-text="item.name"></h4>

                            <template x-if="item.glass_code">
                                <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400">
                                    Code: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="item.glass_code"></span>
                                </div>
                            </template>

                            <template x-if="item.price && item.price > 0">
                                <div class="text-xs sm:text-sm font-black text-slate-900 dark:text-white font-mono"
                                     x-text="typeof window.formatCurrency === 'function' ? window.formatCurrency(item.price) : Number(item.price).toLocaleString()"></div>
                            </template>
                        </div>

                        {{-- Action Buttons Row --}}
                        <div class="flex items-center gap-1 mt-auto pt-1.5 border-t border-slate-200/80 dark:border-slate-700/80">
                            {{-- Move to Cart 3D Button --}}
                            <button type="button"
                                    @click.stop.prevent="item.glass_code ? $store.orderBuilder.addGlassCodeItem(item.glass_code, item.name, item.glass_finder_item_id) : $store.orderBuilder.addItem(item)"
                                    class="sf-btn-3d-primary flex-1 py-1 sm:py-1.5 rounded-md text-xs font-black flex items-center justify-center gap-1 cursor-pointer select-none shadow-2xs"
                                    title="{{ __('messages.favorites_move_to_cart') }}">
                                <span class="text-xs">🛒</span>
                                <span>+ Cart</span>
                                <span x-show="$store.orderBuilder && ($store.orderBuilder.getItemQty(item.product_id || item.id) > 0 || $store.orderBuilder.getGlassItemQty(item.glass_finder_item_id) > 0)"
                                      class="px-1.5 py-0.2 rounded-full bg-white text-orange-600 font-black text-[10px]"
                                      x-text="$store.orderBuilder.getItemQty(item.product_id || item.id) || $store.orderBuilder.getGlassItemQty(item.glass_finder_item_id)"></span>
                            </button>

                            {{-- Details Link --}}
                            <a :href="item.url || '{{ url('/products') }}'"
                               class="sf-btn-3d px-2 py-1 sm:py-1.5 rounded-md text-xs font-bold"
                               title="{{ __('messages.order_view_detail') }}">
                                →
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- 5. Empty State (When both Server & Client items are empty) --}}
        @if (!$hasCloudFavorites)
            <div x-show="!$store.favoritesStore || $store.favoritesStore.items.length === 0"
                 class="text-center py-10 sm:py-12 px-3 space-y-3">
                <div class="w-14 h-14 sm:w-16 sm:h-16 mx-auto rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-500 flex items-center justify-center text-3xl sm:text-4xl shadow-2xs border border-rose-200/80 dark:border-rose-900/60">
                    💔
                </div>
                <div class="space-y-1">
                    <h3 class="font-black text-sm sm:text-base text-slate-900 dark:text-white">
                        {{ __('messages.favorites_empty_title') }}
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium max-w-sm mx-auto leading-relaxed">
                        {{ __('messages.favorites_empty_subtitle') }}
                    </p>
                </div>
                <div class="flex items-center justify-center gap-2 pt-2 flex-wrap">
                    <a href="{{ $productsUrl }}"
                       class="sf-btn-3d-primary !inline-flex items-center gap-1.5 px-4 py-2 rounded-md text-xs font-black shadow-xs">
                        <span>🛍️</span>
                        <span>{{ __('messages.favorites_browse_more') }}</span>
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="{{ $glassFinderUrl }}"
                       class="sf-btn-3d !inline-flex items-center gap-1.5 px-3.5 py-2 rounded-md text-xs font-bold">
                        <span>🔍</span>
                        <span>{{ __('messages.glass_finder') }}</span>
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- 6. Storefront Trust Highlights Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 sm:gap-2 text-center text-xs pt-1">
        <div class="p-2 sm:p-2.5 rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-sm sm:text-base">🛡️</span>
            <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">100% Authentic</p>
        </div>
        <div class="p-2 sm:p-2.5 rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-sm sm:text-base">💵</span>
            <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">Cash On Delivery</p>
        </div>
        <div class="p-2 sm:p-2.5 rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-sm sm:text-base">🚚</span>
            <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">Fast Delivery</p>
        </div>
        <div class="p-2 sm:p-2.5 rounded-lg sm:rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-sm sm:text-base">⭐</span>
            <p class="font-bold text-slate-800 dark:text-slate-200 text-[11px]">Service Guaranteed</p>
        </div>
    </div>
</div>
@endsection
