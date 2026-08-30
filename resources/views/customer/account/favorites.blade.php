@extends('layouts.storefront.app')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $accountUrl = $storeSlug ? url('/account?store_slug=' . $storeSlug) : url('/account');
    $productsUrl = $storeSlug ? url('/products?store_slug=' . $storeSlug) : url('/products');
    $glassFinderUrl = $storeSlug ? url('/glass-finder?store_slug=' . $storeSlug) : url('/glass-finder');
    $orderBuilderUrl = $storeSlug ? url('/order-builder?store_slug=' . $storeSlug) : url('/order-builder');
@endphp

<div class="max-w-6xl mx-auto space-y-6 pb-12">
    {{-- Header & Top Navigation --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-xs">
        <div class="space-y-1">
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-black tracking-wide text-white uppercase shadow-2xs border-0"
                 style="background: linear-gradient(135deg, #e11d48 0%, #f43f5e 100%) !important;">
                <span>❤️</span>
                <span>My Saved Favorites</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-sans">
                သိမ်းဆည်းထားသော ပစ္စည်းများ (Favorites)
            </h1>
            <p class="text-xs text-slate-600 dark:text-slate-400 font-myanmar">
                သင် နှစ်သက်၍ သိမ်းဆည်းထားသော ဖုန်းမှန်များနှင့် ပစ္စည်းများကို အလွယ်တကူ ပြန်လည်ကြည့်ရှု၍ အော်ဒါတင်နိုင်ပါသည်
            </p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap">
            <a href="{{ $orderBuilderUrl }}"
               style="background: linear-gradient(135deg, #f85606 0%, #ea580c 100%) !important; color: #ffffff !important;"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-black text-white shadow-md shadow-orange-500/20 hover:brightness-110 active:scale-95 transition cursor-pointer select-none border-0">
                <span>🛒</span>
                <span>အော်ဒါစာရင်း ကြည့်မည်</span>
                <span x-show="$store.orderBuilder && $store.orderBuilder.items.length > 0"
                      class="px-1.5 py-0.2 rounded-full bg-white text-orange-600 font-black text-[10px]"
                      x-text="$store.orderBuilder ? $store.orderBuilder.items.length : 0"></span>
            </a>
            <a href="{{ auth()->check() ? $accountUrl : url('/') }}"
               class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                <span>&larr;</span>
                <span>{{ auth()->check() ? 'Dashboard' : 'ပင်မစာမျက်နှာ' }}</span>
            </a>
        </div>
    </div>

    {{-- Main Container --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-4 sm:p-6 border border-slate-200/90 dark:border-slate-800/80 shadow-sm space-y-5">
        {{-- Section Header Bar --}}
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3 flex-wrap gap-2">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-500 flex items-center justify-center text-sm shadow-2xs">
                    ❤️
                </span>
                <div>
                    <h2 class="font-black text-sm sm:text-base text-slate-900 dark:text-white">
                        သိမ်းဆည်းထားသော စာရင်း
                    </h2>
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400"
                          x-text="($store.favoritesStore ? $store.favoritesStore.count : 0) + ' items saved'"></span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ $productsUrl }}"
                   class="inline-flex items-center gap-1 text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline">
                    <span>+ ပစ္စည်းများ ထပ်ရှာရန်</span>
                </a>
            </div>
        </div>

        {{-- DB Favorites (For Logged in User) --}}
        @auth
            @if ($favorites && $favorites->count() > 0)
                <div class="mb-4 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span>☁️</span>
                            <span>Account Cloud Favorites</span>
                        </span>
                        <span class="text-[11px] text-slate-400">Account တွင် သိမ်းဆည်းထားသော စာရင်း</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                        @foreach ($favorites as $favorite)
                            @if ($favorite->glassItem)
                                @php
                                    $_brandStr = (string) ($favorite->glassItem->brand ?? 'G');
                                    $_hue = 0;
                                    for ($_i = 0; $_i < strlen($_brandStr); $_i++) { $_hue += (ord($_brandStr[$_i]) * ($_i + 3)) % 360; }
                                    $cloudHue = $_hue % 360;
                                @endphp
                                <div class="cloud-fav-row bg-slate-50/60 dark:bg-slate-800/50 rounded-2xl border border-slate-200/90 dark:border-slate-700/80 p-3 sm:p-3.5 flex flex-col gap-2.5 overflow-hidden shadow-2xs hover:shadow-md transition">
                                    {{-- Brand placeholder tile --}}
                                    <div class="relative -mx-3 -mt-3 sm:-mx-3.5 sm:-mt-3.5 mb-1 aspect-square overflow-hidden cloud-hue-bg rounded-t-2xl"
                                         style="--cloud-hue: {{ $cloudHue }}">
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="p-3.5 rounded-full bg-white/70 dark:bg-slate-900/50 backdrop-blur-md shadow-sm ring-1 ring-white/50">
                                                <span class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white">{{ strtoupper(mb_substr($favorite->glassItem->brand ?? 'G', 0, 1)) }}</span>
                                            </div>
                                        </div>
                                        <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded-full bg-slate-900/60 text-white text-[10px] font-mono font-bold backdrop-blur-sm shadow-xs">
                                            GLASS
                                        </span>
                                    </div>

                                    <div class="flex items-start justify-between gap-1.5">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-sky-100 dark:bg-sky-950 text-sky-800 dark:text-sky-300 border border-sky-200 dark:border-sky-800 uppercase">
                                            {{ $favorite->glassItem->brand }}
                                        </span>
                                        <button
                                            type="button"
                                            @click.prevent="$store.favoritesStore.removeServerItem({{ $favorite->glassItem->id }}, $el)"
                                            class="w-7 h-7 flex items-center justify-center text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded-lg transition font-bold cursor-pointer"
                                            title="Remove Favorite"
                                            aria-label="Remove Favorite"
                                        >
                                            🗑️
                                        </button>
                                    </div>

                                    <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white break-words leading-tight line-clamp-2">
                                        {{ $favorite->glassItem->phone_model }}
                                    </h4>

                                    <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">
                                        Code: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $favorite->glassItem->glass_code }}</span>
                                    </div>

                                    <div class="flex items-center gap-1.5 mt-auto pt-2 border-t border-slate-200/80 dark:border-slate-700/80">
                                        <button 
                                            @click.stop.prevent="$store.orderBuilder.addItem({ glass_finder_item_id: {{ $favorite->glassItem->id }}, name: 'Glass: {{ addslashes($favorite->glassItem->phone_model) }} ({{ $favorite->glassItem->glass_code }})', price: 0, sku: {{ json_encode($favorite->glassItem->glass_code) }} })"
                                            type="button"
                                            style="background: linear-gradient(135deg, #f85606 0%, #ea580c 100%) !important; color: #ffffff !important;"
                                            class="flex-1 min-h-[34px] px-2.5 py-1.5 text-white rounded-xl text-xs font-black shadow-xs hover:brightness-110 active:scale-95 transition flex items-center justify-center gap-1 cursor-pointer select-none border-0"
                                            title="အော်ဒါ စာရင်းသို့ ထည့်မည်"
                                        >
                                            <span class="text-xs">🛒</span>
                                            <span>+ Cart</span>
                                            <span x-show="$store.orderBuilder && $store.orderBuilder.getGlassItemQty({{ $favorite->glassItem->id }}) > 0" class="px-1.5 py-0.2 rounded-full bg-white text-orange-600 font-black text-[10px]" x-text="$store.orderBuilder.getGlassItemQty({{ $favorite->glassItem->id }})"></span>
                                        </button>
                                        <a href="{{ url('/glass-finder?phone_model=' . urlencode($favorite->glassItem->phone_model)) }}" class="px-2.5 py-1.5 bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-600 rounded-xl text-xs font-bold transition">
                                            Finder &rarr;
                                        </a>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        @endauth

        {{-- Empty State --}}
        <div x-show="!$store.favoritesStore || $store.favoritesStore.items.length === 0" class="text-center py-12 px-4 space-y-3">
            <div class="w-16 h-16 rounded-full bg-rose-50 dark:bg-rose-950/60 text-rose-500 flex items-center justify-center text-2xl mx-auto shadow-inner">
                💔
            </div>
            <h3 class="font-black text-base text-slate-800 dark:text-slate-200">မည်သည့် ပစ္စည်းမျှ သိမ်းဆည်းထားခြင်း မရှိသေးပါ</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar max-w-md mx-auto leading-relaxed">
                ဆိုင်သုံး ပစ္စည်းများ သို့မဟုတ် Glass Finder မှ အသည်းပုံ (❤️) ကို နှိပ်၍ မိမိနှစ်သက်သော ပစ္စည်းများကို အလွယ်တကူ သိမ်းဆည်းထားနိုင်ပါသည်
            </p>
            <div class="flex items-center justify-center gap-2 pt-2 flex-wrap">
                <a href="{{ $productsUrl }}"
                   style="background: linear-gradient(135deg, #f85606 0%, #ea580c 100%) !important; color: #ffffff !important;"
                   class="px-5 py-2.5 rounded-xl text-white font-black text-xs shadow-md shadow-orange-500/20 hover:brightness-110 active:scale-95 transition cursor-pointer border-0">
                    🛍️ ပစ္စည်းများ ကြည့်ရှုမည် &rarr;
                </a>
                <a href="{{ $glassFinderUrl }}"
                   class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    🔍 Glass Finder ရှာဖွေမည်
                </a>
            </div>
        </div>

        {{-- Favorites Item Cards (Client / LocalStorage & Merged) --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <template x-for="item in ($store.favoritesStore ? $store.favoritesStore.items : [])" :key="item.id">
                <div class="bg-slate-50/60 dark:bg-slate-800/50 rounded-2xl border border-slate-200/90 dark:border-slate-700/80 p-3 sm:p-3.5 flex flex-col gap-2.5 overflow-hidden shadow-2xs hover:shadow-md transition" x-data="{ h: brandHue(item.brand) }">
                    {{-- Full-bleed image tile --}}
                    <div class="relative -mx-3 -mt-3 sm:-mx-3.5 sm:-mt-3.5 mb-1 aspect-square overflow-hidden bg-white dark:bg-slate-800/80 rounded-t-2xl flex items-center justify-center">
                        <img x-show="item.image_path"
                             :src="item.image_path ? '{{ asset('storage') }}/' + item.image_path : null"
                             :alt="item.name"
                             loading="lazy"
                             decoding="async"
                             class="w-full h-full object-contain p-2"
                             data-img-fallback="fav">
                        <div x-show="!item.image_path" data-fav-ph
                             class="absolute inset-0 flex items-center justify-center cloud-hue-bg"
                             :style="'--cloud-hue: ' + h">
                            <div class="p-3.5 rounded-full bg-white/70 dark:bg-slate-900/50 backdrop-blur-md shadow-sm ring-1 ring-white/50">
                                <span class="text-2xl sm:text-3xl font-black text-slate-800 dark:text-white" x-text="(item.brand || 'G').charAt(0).toUpperCase()"></span>
                            </div>
                        </div>
                        <span x-show="item.glass_code" class="absolute bottom-2 right-2 px-2 py-0.5 rounded-full bg-slate-900/60 text-white text-[10px] font-mono font-bold backdrop-blur-sm shadow-xs" x-text="item.glass_code"></span>
                    </div>

                    <div class="flex items-start justify-between gap-1.5">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-sky-100 dark:bg-sky-950 text-sky-800 dark:text-sky-300 border border-sky-200 dark:border-sky-800 uppercase truncate" x-text="item.brand || 'General'"></span>
                        <button @click="$store.favoritesStore.removeItem(item.id)" type="button" class="w-7 h-7 flex items-center justify-center text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded-lg font-bold transition cursor-pointer" title="Remove Favorite" aria-label="Remove Favorite">
                            🗑️
                        </button>
                    </div>

                    <h4 class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white break-words leading-tight line-clamp-2" x-text="item.name"></h4>

                    <template x-if="item.glass_code">
                        <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">
                            Code: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="item.glass_code"></span>
                        </div>
                    </template>

                    <template x-if="item.price && item.price > 0">
                        <div class="text-xs sm:text-sm font-black text-[#f85606] dark:text-orange-400 font-sans" x-text="'Ks ' + item.price.toLocaleString()"></div>
                    </template>

                    <div class="flex items-center gap-1.5 mt-auto pt-2 border-t border-slate-200/80 dark:border-slate-700/80">
                        {{-- 🛒 + Cart Button --}}
                        <button 
                            @click.stop.prevent="item.glass_code ? $store.orderBuilder.addGlassCodeItem(item.glass_code, item.name, item.glass_finder_item_id) : $store.orderBuilder.addItem(item)"
                            type="button"
                            style="background: linear-gradient(135deg, #f85606 0%, #ea580c 100%) !important; color: #ffffff !important;"
                            class="flex-1 min-h-[34px] px-2.5 py-1.5 text-white rounded-xl text-xs font-black shadow-xs hover:brightness-110 active:scale-95 transition flex items-center justify-center gap-1 cursor-pointer select-none border-0"
                            title="အော်ဒါ စာရင်းသို့ ထည့်မည်"
                        >
                            <span class="text-xs">🛒</span>
                            <span>+ Cart</span>
                            <span x-show="$store.orderBuilder && ($store.orderBuilder.getItemQty(item.product_id || item.id) > 0 || $store.orderBuilder.getGlassItemQty(item.glass_finder_item_id) > 0)" class="px-1.5 py-0.2 rounded-full bg-white text-orange-600 font-black text-[10px]" x-text="$store.orderBuilder.getItemQty(item.product_id || item.id) || $store.orderBuilder.getGlassItemQty(item.glass_finder_item_id)"></span>
                        </button>

                        <a :href="item.url || '{{ url('/products') }}'" class="px-2.5 py-1.5 bg-slate-200/80 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-600 rounded-xl text-xs font-bold transition">
                            ကြည့်ရှု &rarr;
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Trust Badges Row --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
        <div class="p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-base">🛡️</span>
            <p class="font-bold text-slate-700 dark:text-slate-300 text-[11px]">100% Authentic</p>
        </div>
        <div class="p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-base">💵</span>
            <p class="font-bold text-slate-700 dark:text-slate-300 text-[11px]">Cash On Delivery</p>
        </div>
        <div class="p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-base">🚚</span>
            <p class="font-bold text-slate-700 dark:text-slate-300 text-[11px]">Fast Delivery</p>
        </div>
        <div class="p-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-0.5">
            <span class="text-base">⭐</span>
            <p class="font-bold text-slate-700 dark:text-slate-300 text-[11px]">Service Guaranteed</p>
        </div>
    </div>
</div>
@endsection
