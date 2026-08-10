@extends('layouts.storefront.app')

@section('content')
@php
    $accountUrl = $store ? url('/account?store_slug=' . $store->slug) : url('/account');
    $productsUrl = $store ? url('/products?store_slug=' . $store->slug) : url('/products');
@endphp
<div class="max-w-6xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="inline-flex items-center space-x-1.5 px-3 py-0.5 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 text-xs font-extrabold border border-rose-400/30 mb-1">
                <span>❤️ My Saved Favorites</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white font-outfit">
                သိမ်းဆည်းထားသည်များ (Favorites)
            </h1>
            <p class="text-xs text-slate-600 dark:text-slate-400 font-myanmar">
                သင် နှစ်သက်၍ သိမ်းဆည်းထားသော ဖုန်းမှန်များနှင့် ပစ္စည်းများ
            </p>
        </div>
        <a href="{{ auth()->check() ? $accountUrl : url('/') }}" class="text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline">
            &larr; {{ auth()->check() ? 'Account Dashboard' : 'ပင်မစာမျက်နှာ သို့' }}
        </a>
    </div>

    {{-- Main Container --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/90 dark:border-slate-800/80 shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200/60 dark:border-slate-800/60 pb-3">
            <h2 class="font-black text-base text-slate-900 dark:text-white font-outfit flex items-center space-x-2">
                <span>❤️</span>
                <span>သိမ်းဆည်းထားသော ပစ္စည်းများ စာရင်း</span>
            </h2>
            <span class="text-xs font-extrabold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950 px-2.5 py-1 rounded-full border border-rose-200 dark:border-rose-800" x-text="($store.favoritesStore ? $store.favoritesStore.count : 0) + ' Saved'"></span>
        </div>

        {{-- DB Favorites (For Logged in User) --}}
        @auth
            @if ($favorites && $favorites->count() > 0)
                <div class="mb-4 pb-4 border-b border-slate-200/60 dark:border-slate-800/60">
                    <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Account Cloud Favorites</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach ($favorites as $favorite)
                            @if ($favorite->glassItem)
                                @php
                                    // Mirrors window.brandHue() in app.js so cloud + client cards color the same brand identically.
                                    $_brandStr = (string) ($favorite->glassItem->brand ?? 'G');
                                    $_hue = 0;
                                    for ($_i = 0; $_i < strlen($_brandStr); $_i++) { $_hue += (ord($_brandStr[$_i]) * ($_i + 3)) % 360; }
                                    $cloudHue = $_hue % 360;
                                @endphp
                                <div class="cloud-fav-row bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800/80 p-4 flex flex-col gap-2.5 overflow-hidden">
                                    {{-- Brand placeholder tile (glass items have no photo) --}}
                                <div class="relative -mx-4 -mt-4 mb-1 aspect-square overflow-hidden cloud-hue-bg"
                                     style="--cloud-hue: {{ $cloudHue }}">
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <div class="p-4 rounded-full bg-white/60 dark:bg-slate-900/40 backdrop-blur-md shadow-sm ring-1 ring-white/50">
                                                <span class="text-3xl font-black text-slate-700/80 dark:text-white/80">{{ strtoupper(mb_substr($favorite->glassItem->brand ?? 'G', 0, 1)) }}</span>
                                            </div>
                                        </div>
                                        <span class="absolute bottom-2 right-2 px-2 py-0.5 rounded-full bg-slate-900/50 text-white/90 text-xs font-mono font-bold backdrop-blur-sm">GLASS</span>
                                    </div>
                                    <div class="flex items-start justify-between gap-2">
                                        <span class="px-2 py-0.5 rounded text-xs font-extrabold bg-sky-100 dark:bg-sky-950 text-sky-800 dark:text-sky-300 border border-sky-300 dark:border-sky-800 uppercase">
                                            {{ $favorite->glassItem->brand }}
                                        </span>
                                        <button
                                            type="button"
                                            @click.prevent="$store.favoritesStore.removeServerItem({{ $favorite->glassItem->id }}, $el)"
                                            class="p-1.5 -m-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded-lg transition font-bold"
                                            title="Remove Favorite"
                                            aria-label="Remove Favorite"
                                        >
                                            🗑️
                                        </button>
                                    </div>
                                    <h4 class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white break-words leading-snug">
                                        {{ $favorite->glassItem->phone_model }}
                                    </h4>
                                    <div class="text-xs font-mono text-slate-500 dark:text-slate-400">
                                        Code: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $favorite->glassItem->glass_code }}</span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 mt-auto pt-2.5 border-t border-slate-100 dark:border-slate-800/80">
                                        <button 
                                            @click.stop.prevent="$store.orderBuilder.addItem({ glass_finder_item_id: {{ $favorite->glassItem->id }}, name: 'Glass: {{ addslashes($favorite->glassItem->phone_model) }} ({{ $favorite->glassItem->glass_code }})', price: 0, sku: {{ json_encode($favorite->glassItem->glass_code) }} })"
                                            type="button"
                                            class="relative px-3 py-2 bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 hover:from-violet-600 hover:to-rose-500 text-white rounded-xl text-xs font-extrabold shadow-md shadow-sky-500/20 flex items-center space-x-1 transition active:scale-95"
                                            title="အော်ဒါ စာရင်းသို့ ထည့်မည်"
                                        >
                                            <span>🛒</span>
                                            <span>+ Cart</span>
                                            <span x-show="$store.orderBuilder && $store.orderBuilder.getGlassItemQty({{ $favorite->glassItem->id }}) > 0" class="px-1.5 py-0.2 rounded-full bg-rose-500 text-white font-black text-xs border border-white" x-text="$store.orderBuilder.getGlassItemQty({{ $favorite->glassItem->id }})"></span>
                                        </button>
                                        <a href="{{ url('/glass-finder?phone_model=' . urlencode($favorite->glassItem->phone_model)) }}" class="px-3.5 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl text-xs font-bold transition">
                                            Glass Finder &rarr;
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
        <div x-show="!$store.favoritesStore || $store.favoritesStore.items.length === 0" class="text-center py-12 space-y-3">
            <div class="text-4xl">💔</div>
            <h3 class="font-bold text-sm text-slate-700 dark:text-slate-300">မည်သည့် ပစ္စည်းမျှ သိမ်းဆည်းထားခြင်း မရှိသေးပါ</h3>
            <p class="text-xs text-slate-500 font-myanmar">Catalog သို့မဟုတ် Glass Finder မှ အသည်းပုံ (❤️) ကို နှိပ်၍ အလွယ်တကူ သိမ်းဆည်းပါ</p>
            <a href="{{ $productsUrl }}" class="inline-block mt-2 px-5 py-2.5 rounded-xl bg-sky-600 text-white font-bold text-xs shadow-md hover:bg-sky-500 transition">
                ပစ္စည်းများ ကြည့်ရှုမည် &rarr;
            </a>
        </div>

        {{-- Favorites Item Cards (full name, responsive 1/2/3 grid) --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            <template x-for="item in ($store.favoritesStore ? $store.favoritesStore.items : [])" :key="item.id">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/90 dark:border-slate-800/80 p-4 flex flex-col gap-2.5 overflow-hidden" x-data="{ h: brandHue(item.brand) }">
                    {{-- Full-bleed image tile (photo when available, brand-gradient placeholder otherwise) --}}
                    <div class="relative -mx-4 -mt-4 mb-1 aspect-square overflow-hidden bg-slate-100 dark:bg-slate-800">
                        <img x-show="item.image_path"
                             :src="item.image_path ? '{{ asset('storage') }}/' + item.image_path : null"
                             :alt="item.name"
                             loading="lazy"
                             decoding="async"
                             class="w-full h-full object-contain"
                             data-img-fallback="fav">
                        <div x-show="!item.image_path" data-fav-ph
                             class="absolute inset-0 flex items-center justify-center cloud-hue-bg"
                             :style="'--cloud-hue: ' + h">
                            <div class="p-4 rounded-full bg-white/60 dark:bg-slate-900/40 backdrop-blur-md shadow-sm ring-1 ring-white/50">
                                <span class="text-3xl font-black text-slate-700/80 dark:text-white/80" x-text="(item.brand || 'G').charAt(0).toUpperCase()"></span>
                            </div>
                        </div>
                        <span x-show="item.glass_code" class="absolute bottom-2 right-2 px-2 py-0.5 rounded-full bg-slate-900/50 text-white/90 text-xs font-mono font-bold backdrop-blur-sm" x-text="item.glass_code"></span>
                    </div>
                    <div class="flex items-start justify-between gap-2">
                        <span class="px-2 py-0.5 rounded text-xs font-extrabold bg-sky-100 dark:bg-sky-950 text-sky-800 dark:text-sky-300 border border-sky-300 dark:border-sky-800 uppercase" x-text="item.brand || 'General'"></span>
                        <button @click="$store.favoritesStore.removeItem(item.id)" type="button" class="p-1.5 -m-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/60 rounded-lg font-bold transition" title="Remove Favorite" aria-label="Remove Favorite">
                            🗑️
                        </button>
                    </div>
                    <h4 class="font-extrabold text-sm sm:text-base text-slate-900 dark:text-white break-words leading-snug" x-text="item.name"></h4>
                    <template x-if="item.glass_code">
                        <div class="text-xs font-mono text-slate-500 dark:text-slate-400">
                            Code: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="item.glass_code"></span>
                        </div>
                    </template>
                    <template x-if="item.price && item.price > 0">
                        <div class="text-xs font-black text-sky-600 dark:text-sky-400 font-outfit" x-text="'Ks ' + item.price.toLocaleString()"></div>
                    </template>

                    <div class="flex flex-wrap items-center gap-2 mt-auto pt-2.5 border-t border-slate-100 dark:border-slate-800/80">
                        {{-- 🛒 + Cart Button --}}
                        <button 
                            @click.stop.prevent="item.glass_code ? $store.orderBuilder.addGlassCodeItem(item.glass_code, item.name, item.glass_finder_item_id) : $store.orderBuilder.addItem(item)"
                            type="button"
                            class="relative px-3 py-2 bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 hover:from-violet-600 hover:to-rose-500 text-white rounded-xl text-xs font-extrabold shadow-md shadow-sky-500/20 flex items-center space-x-1 transition active:scale-95"
                            title="အော်ဒါ စာရင်းသို့ ထည့်မည်"
                        >
                            <span>🛒</span>
                            <span>+ Cart</span>
                            <span x-show="$store.orderBuilder && ($store.orderBuilder.getItemQty(item.product_id || item.id) > 0 || $store.orderBuilder.getGlassItemQty(item.glass_finder_item_id) > 0)" class="px-1.5 py-0.2 rounded-full bg-rose-500 text-white font-black text-xs border border-white" x-text="$store.orderBuilder.getItemQty(item.product_id || item.id) || $store.orderBuilder.getGlassItemQty(item.glass_finder_item_id)"></span>
                        </button>

                        <a :href="item.url || '{{ url('/products') }}'" class="px-3.5 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl text-xs font-bold transition">
                            ကြည့်ရှုမည် &rarr;
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
