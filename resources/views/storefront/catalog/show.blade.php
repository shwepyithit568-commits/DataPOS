@extends('layouts.storefront.app')
@php
    $hideFloatingFabs = true;
@endphp

{{-- Product-level SEO + Open Graph so Facebook / Telegram / Viber show an
     image-rich preview when a product link is shared. The meta description
     fallback chain lives in App\Support\SeoMeta (central helper) and is
     rendered once by the storefront layout — never as page body content. --}}
@php
    $ogImagePath = $product->all_image_paths[0] ?? $product->image_path;
    // Product image first; otherwise fall back to the store's share logo so
    // shared product links always carry an image (layout's $setting fallback
    // cannot fire on product pages because $setting is not passed there).
    $storeSetting = $store?->setting;
    $shareLogo = $storeSetting?->storefrontLogo();
    $ogImage = $ogImagePath ? asset('storage/' . $ogImagePath) : ($shareLogo ? asset('storage/' . $shareLogo) : null);
    $ogTitle = $product->name;
    $ogType = 'product';
    $canonicalUrl = url('/store/' . ($store?->slug ?? request('store_slug')) . '/product/' . $product->slug);
    $metaDescription = \App\Support\SeoMeta::descriptionFor(
        $product->meta_description,
        $product->description,
        $product->name,
        $product->brand?->name,
        $product->category?->name,
        $store?->name ?? config('app.name'),
    );
@endphp

@section('content')
@php
    $gallery = $product->all_image_paths;
    $primaryImage = $gallery[0] ?? $product->image_path;
    $effectivePrice = $isWholesaleApproved && $product->wholesale_price > 0 ? $product->wholesale_price : $product->retail_price;
    $showRetailSale = ! $isWholesaleApproved && $product->isOnSale();

    $storeSlug = $store?->slug ?? request('store_slug');
    $productUrl = url("/store/{$storeSlug}/product/{$product->slug}");
    $storeSetting = $store?->setting;
    $directOrderText = "မင်္ဂလာပါ။\n"
        . ($store?->name ?? config('app.name')) . " မှာ အောက်ပါပစ္စည်းကို အော်ဒါတင်ချင်ပါတယ်။\n\n"
        . "ပစ္စည်း: {$product->name}\n"
        . "SKU: " . ($product->sku ?: '-') . "\n"
        . "ဈေးနှုန်း: " . format_currency($effectivePrice, $store) . "\n"
        . "လင့်ခ်: {$productUrl}";
    $directViberUrl = \App\Support\ContactLinkBuilder::viberChatUrl($storeSetting?->viber_number, $directOrderText);
    // iOS swap must carry the same draft — viber://contact can't hold a draft,
    // so the order message (product name + details) would be lost on iPhone.
    $directViberIosUrl = \App\Support\ContactLinkBuilder::viberIosContactUrl($storeSetting?->viber_number, $directOrderText);
    $directTelegramUrl = \App\Support\ContactLinkBuilder::telegramUrl($storeSetting?->telegram_username, $directOrderText);
    // Normalized channel targets — reused client-side so the Direct Order links
    // rebuild the message (with the selected variant) as the shopper switches.
    $directViberNumber = \App\Support\ContactLinkBuilder::normalizeMyanmarPhone($storeSetting?->viber_number);
    $directTelegramUser = \App\Support\ContactLinkBuilder::telegramUsername($storeSetting?->telegram_username);
    $shareText = $product->name . ' — ' . format_currency($effectivePrice, $store) . ' — ' . ($store?->name ?? config('app.name'));
@endphp

<div class="w-full mx-auto space-y-1 sm:space-y-1.5 lg:space-y-2 pb-[70px] md:pb-0"
    x-data="{
        variants: @js($product->variants->map(fn($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'attributes' => $v->attributes ?? [],
            'sku' => $v->sku,
            'retail_price' => (float) $v->retail_price,
            'wholesale_price' => $v->wholesale_price !== null ? (float) $v->wholesale_price : null,
            'stock_status' => $v->stock_status,
            'image_path' => $v->image_path,
            'is_default' => (bool) $v->is_default,
        ])),
        selectedIndex: 0,
        selectedAttrs: {},
        activeImage: @js($primaryImage),
        isWholesale: {{ $isWholesaleApproved ? 'true' : 'false' }},
        baseRetail: {{ (float) $product->retail_price }},
        baseWhole: {{ (float) $product->wholesale_price }},
        baseSku: @js($product->sku),
        baseOld: {{ $showRetailSale ? (float) $product->old_price : 'null' }},
        baseName: @js($product->name),
        productUrl: @js($productUrl),
        storeName: @js($store?->name ?? config('app.name')),
        viberNumber: @js($directViberNumber !== null ? ltrim($directViberNumber, '+') : null),
        telegramUser: @js($directTelegramUser),
        get hasVariants() { return this.variants.length > 0; },
        get selected() { return this.variants[this.selectedIndex] ?? null; },
        get price() {
            if (this.selected) {
                const p = this.isWholesale && (this.selected.wholesale_price || 0) > 0 ? this.selected.wholesale_price : this.selected.retail_price;
                // Name-only variant rows (imports without variant prices) must
                // never show 'Ks 0' — fall back to the product price.
                return p > 0 ? p : (this.isWholesale && this.baseWhole > 0 ? this.baseWhole : this.baseRetail);
            }
            return this.isWholesale && this.baseWhole > 0 ? this.baseWhole : this.baseRetail;
        },
        get sku() { return this.selected ? (this.selected.sku || this.baseSku) : this.baseSku; },
        get variantId() { return this.selected ? this.selected.id : null; },
        get cartName() { return this.selected ? @js($product->name) + ' - ' + this.selected.name : @js($product->name); },
        // Direct-order message — rebuilt reactively so the selected variant's
        // name/SKU/price ride along in the Viber draft and Telegram text.
        get orderName() { return this.selected ? @js($product->name) + ' - ' + this.selected.name : @js($product->name); },
        get orderSku() { return this.sku; },
        get orderDraft() {
            return 'မင်္ဂလာပါ။\n'
                + this.storeName + ' မှာ အောက်ပါပစ္စည်းကို အော်ဒါတင်ချင်ပါတယ်။\n\n'
                + 'ပစ္စည်း: ' + this.orderName + '\n'
                + 'SKU: ' + (this.orderSku || '-') + '\n'
                + 'ဈေးနှုန်း: ' + this.fmt(this.price) + '\n'
                + 'လင့်ခ်: ' + this.productUrl;
        },
        // Use the canonical JS helper (mirrors ContactLinkBuilder) so the URL
        // stays in sync with the server-rendered no-JS fallbacks.
        get viberHref() {
            return window.alinnViber ? window.alinnViber.buildViberChatUrl(this.viberNumber, this.orderDraft) : null;
        },
        get viberIosHref() { return this.viberHref; },
        get telegramHref() {
            return this.telegramUser ? 'https://t.me/' + this.telegramUser + '?text=' + encodeURIComponent(this.orderDraft) : null;
        },
        // No variants selected → fall back to the product's own stock status so
        // the reactive label can't contradict the server-rendered badge.
        get inStock() { return this.selected ? this.selected.stock_status === 'in_stock' : {{ $product->stock_status === 'in_stock' ? 'true' : 'false' }}; },
        get onSale() { return !this.isWholesale && this.baseOld !== null && this.baseOld > this.price; },
        get discountPct() { if (!this.onSale) return 0; return Math.round(((this.baseOld - this.price) / this.baseOld) * 100); },
        fmt(n) { return (typeof window.formatCurrency === 'function') ? window.formatCurrency(n) : Number(n).toLocaleString('en-US'); },

        // --- Viber Order Modal state ---
        viberModalOpen: false,
        viberQty: 1,
        viberCopied: 'none', // 'none' | 'copying' | 'copied' | 'failed'
        viberOpening: false,
        _viberLastTrigger: 0,
        phoneForTel: @js($storeSetting?->phone ?: null),
        get viberNeedsVariant() {
            return this.variants.length > 0 && !this.selected;
        },
        get viberOrderMessage() {
            if (this.viberNeedsVariant) return '';
            const v = window.alinnViber;
            if (!v) return this.orderDraft;
            return v.buildOrderMessage({
                store_name: this.storeName,
                product_name: this.orderName,
                sku: this.orderSku,
                variant_name: this.selected ? this.selected.name : null,
                quantity: this.viberQty,
                unit_price: this.price,
                total_price: this.price * this.viberQty,
                product_url: this.productUrl,
            });
        },
        get viberModalHref() {
            if (this.viberNeedsVariant || !this.viberNumber) return null;
            const v = window.alinnViber;
            if (!v) return this.viberHref;
            return v.buildViberChatUrl(this.viberNumber, this.viberModalOrderDraft);
        },
        // Reactive order draft that includes viberQty + variant selection
        get viberModalOrderDraft() {
            const v = window.alinnViber;
            if (!v) return this.orderDraft;
            return v.buildOrderMessage({
                store_name: this.storeName,
                product_name: this.orderName,
                sku: this.orderSku,
                variant_name: this.selected ? this.selected.name : null,
                quantity: this.viberQty,
                unit_price: this.price,
                total_price: this.price * this.viberQty,
                product_url: this.productUrl,
            });
        },
        get viberModalUrl() {
            if (this.viberNeedsVariant || !this.viberNumber) return null;
            const v = window.alinnViber;
            if (!v) return null;
            return v.buildViberChatUrl(this.viberNumber, this.viberModalOrderDraft);
        },
        openViberModal() {
            const store = Alpine.store('viberModal');
            if (!store) return;
            store.init({
                qty: () => this.viberQty,
                price: () => this.price,
                sku: () => this.orderSku,
                variantName: () => this.selected ? this.selected.name : '',
                message: () => {
                    if (this.viberNeedsVariant) return '';
                    const v = window.alinnViber;
                    if (!v) return this.orderDraft;
                    return v.buildOrderMessage({
                        store_name: this.storeName,
                        product_name: this.orderName,
                        sku: this.orderSku,
                        variant_name: this.selected ? this.selected.name : null,
                        quantity: this.viberQty,
                        unit_price: this.price,
                        total_price: this.price * this.viberQty,
                        product_url: this.productUrl,
                    });
                },
                url: () => {
                    if (this.viberNeedsVariant || !this.viberNumber) return null;
                    const v = window.alinnViber;
                    if (!v) return null;
                    return v.buildViberChatUrl(this.viberNumber,
                        v.buildOrderMessage({
                            store_name: this.storeName,
                            product_name: this.orderName,
                            sku: this.orderSku,
                            variant_name: this.selected ? this.selected.name : null,
                            quantity: this.viberQty,
                            unit_price: this.price,
                            total_price: this.price * this.viberQty,
                            product_url: this.productUrl,
                        }));
                },
                needsVariant: () => this.viberNeedsVariant,
                fmt: (n) => Number(n).toLocaleString('en-US'),
                copied: 'none',
                opening: false,
                phone: @js($storeSetting?->phone ?: null),
                incQty: () => { this.viberQty = Math.max(1, this.viberQty + 1); },
                decQty: () => { this.viberQty = Math.max(1, this.viberQty - 1); },
            });
            this.viberQty = 1;
            store.show();
        },
        closeViberModal() {
            if (window.__viberModalState) window.__viberModalState.close();
        },
        incViberQty(d) { /* unused in product detail; modal has its own inc */ },
        async viberCopyMessage() { /* unused */ },
        async viberCopyAndOpen() { /* unused */ },

        // --- Grouped (attribute) selector — used when variants carry attributes ---
        get attrLabels() {
            const labels = [];
            this.variants.forEach(v => (v.attributes || []).forEach(a => {
                if (a && a.label && !labels.includes(a.label)) labels.push(a.label);
            }));
            return labels;
        },
        get hasStructuredAttrs() { return this.attrLabels.length > 0; },
        variantAttrValue(v, label) {
            const attr = (v.attributes || []).find(a => a.label === label);
            return attr ? attr.value : null;
        },
        get visibleVariants() {
            if (!this.hasStructuredAttrs) return this.variants;
            return this.variants.filter(v => this.attrLabels.every(label => {
                const sel = this.selectedAttrs[label];
                if (sel === undefined || sel === null || sel === '') return true;
                return this.variantAttrValue(v, label) === sel;
            }));
        },
        attrValues(label) {
            const values = [];
            this.variants.forEach(v => {
                const value = this.variantAttrValue(v, label);
                if (value && !values.includes(value)) values.push(value);
            });
            return values;
        },
        isAttrAvailable(label, value) {
            // A value is selectable whenever at least one variant carries it.
            // Clicking auto-adjusts the other labels to a matching combination,
            // so sparse combos (e.g. only 256GB-Natural / 512GB-Blue / 1TB-Black)
            // never lock the customer out of switching.
            return this.variants.some(v => this.variantAttrValue(v, label) === value);
        },
        selectAttr(label, value) {
            const candidates = this.variants.filter(v => this.variantAttrValue(v, label) === value);
            if (candidates.length === 0) return;

            const preferred = candidates.find(v =>
                this.attrLabels.every(other => {
                    if (other === label) return true;
                    const sel = this.selectedAttrs[other];
                    if (sel === undefined || sel === null || sel === '') return true;
                    return this.variantAttrValue(v, other) === sel;
                })
            ) ?? candidates[0];

            this.selectedAttrs = {};
            (preferred.attributes || []).forEach(a => { if (a && a.label) this.selectedAttrs[a.label] = a.value; });
            this.selectedIndex = this.variants.indexOf(preferred);
        },
        syncAttrsFromSelection() {
            const v = this.selected;
            const map = {};
            (v?.attributes || []).forEach(a => { if (a && a.label) map[a.label] = a.value; });
            this.selectedAttrs = map;
        },
        syncIndexFromVisible() {
            const visible = this.visibleVariants;
            if (visible.length === 0) return;
            const current = this.selected;
            const idx = visible.indexOf(current);
            const target = visible[idx >= 0 ? idx : 0];
            this.selectedIndex = this.variants.indexOf(target);
        },

        // --- Images: variant image prepends the gallery when the variant has one ---
        get galleryImages() {
            const base = @js($gallery);
            const vi = this.selected && this.selected.image_path ? this.selected.image_path : null;
            return vi ? [vi, ...base.filter(p => p !== vi)] : base;
        },
        get selectedImagePath() {
            return this.selected && this.selected.image_path
                ? this.selected.image_path
                : @js($primaryImage ?? '');
        },

        init() {
            const defaultIndex = this.variants.findIndex(v => v.is_default);
            this.selectedIndex = defaultIndex >= 0 ? defaultIndex : 0;
            this.syncAttrsFromSelection();
            this.$watch('selectedIndex', () => {
                if (this.galleryImages.length) this.activeImage = this.galleryImages[0];
            });
        }
    }">
    {{-- Back Button (right-aligned) --}}
    <div class="flex items-center">
        <a href="{{ url()->previous() }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-slate-600 dark:text-slate-300 hover:text-sky-700 dark:hover:text-sky-300 text-sm font-bold transition active:scale-95 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span>{{ __('messages.back') }}</span>
        </a>
    </div>

    {{-- Main Product Detail --}}
    <div class="w-full bg-white dark:bg-slate-900 border-y border-slate-200/90 dark:border-slate-800/80 shadow-none space-y-5 sm:space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 lg:gap-8">
            {{-- Interactive Image Gallery --}}
            <div class="space-y-2 sm:space-y-3">
                {{-- Main Active Image Hero Box --}}
                <div class="relative overflow-hidden bg-slate-100 dark:bg-slate-800 aspect-square">
                    <template x-if="activeImage">
                        <img 
                            :src="'/storage/' + activeImage" 
                            alt="{{ $product->name }}" 
                            class="w-full h-full object-contain rounded-xl sm:rounded-2xl transition-all duration-300"
                            data-img-fallback="hide-next"
                        />
                    </template>
                    <x-product-image 
                        ::path="activeImage" 
                        :alt="$product->name" 
                        class="w-full h-full object-contain"
                        aspect="aspect-square" 
                    />
                </div>

                {{-- Thumbnail Strip Navigation (server-rendered so search engines see the gallery;
                     the hero image above swaps to the selected variant's image when it has one) --}}
                @if (count($gallery) > 1)
                    <div x-data="{ isDown: false, startX: 0, scrollLeft: 0 }" @mousedown="isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft" @mouseleave="isDown = false" @mouseup="isDown = false" @mousemove="if(isDown){$event.preventDefault();const x=$event.pageX-$el.offsetLeft;const walk=(x-startX)*1.5;$el.scrollLeft=scrollLeft-walk}" class="flex items-center gap-1 sm:gap-2 overflow-x-auto pb-1 sm:pb-1 scrollbar-thin -mx-1 sm:mx-0 px-1 sm:px-0 cursor-grab active:cursor-grabbing select-none">
                        @foreach ($gallery as $img)
                            <button 
                                @click="activeImage = '{{ $img }}'" 
                                type="button" 
                                class="w-11 h-11 sm:w-14 sm:h-14 lg:w-16 lg:h-16 rounded-lg sm:rounded-xl overflow-hidden border-2 transition-all duration-200 shrink-0 focus:outline-none focus:ring-2 focus:ring-sky-500/50"
                                :class="activeImage === '{{ $img }}' ? 'border-sky-500 ring-2 ring-sky-500/30 scale-105 shadow-sm' : 'border-slate-200 dark:border-slate-700 opacity-60 hover:opacity-100'"
                            >
                                <img src="{{ asset('storage/' . $img) }}" alt="{{ __('messages.thumbnail') }}" class="w-full h-full object-cover" />
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Badges Row --}}
                <div class="flex flex-wrap items-center gap-1 sm:gap-2 text-xs sm:text-xs pt-1 sm:pt-1">
                    <span class="px-2 sm:px-3 py-0.5 sm:py-1 rounded-lg sm:rounded-xl font-bold bg-sky-100 dark:bg-sky-950/80 text-sky-800 dark:text-sky-300 border border-sky-300 dark:border-sky-800/60 truncate max-w-[45%] sm:max-w-full">
                        {{ $product->category?->name ?? __('messages.categories') }}
                    </span>
                    <span class="px-2 sm:px-3 py-0.5 sm:py-1 rounded-lg sm:rounded-xl font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 truncate max-w-[50%] sm:max-w-full">
                        {{ __('messages.sku') }}: <span x-text="sku">{{ $product->sku }}</span>
                    </span>
                </div>
            </div>

            {{-- Info & Order Column --}}
            <div class="space-y-3 sm:space-y-5">
                {{-- Brand & Name --}}
                <div class="flex flex-wrap items-baseline gap-x-2 sm:gap-x-3 gap-y-1">
                    <div class="text-xs sm:text-sm font-extrabold uppercase tracking-wider text-sky-700 dark:text-sky-400 shrink-0">
                        {{ $product->brand?->name ?? __('messages.brands') }}
                    </div>
                    <h1 class="text-lg sm:text-2xl lg:text-3xl font-black text-slate-900 dark:text-white font-outfit leading-snug sm:leading-tight">
                        {{ $product->name }}
                    </h1>
                </div>

                {{-- Price Display Box (reactive to selected variant) --}}
                <div class="p-3 sm:p-4 rounded-xl sm:rounded-2xl bg-gradient-to-r from-sky-500/10 via-fuchsia-500/10 to-transparent">
                    <div class="flex flex-wrap items-center gap-x-2 sm:gap-x-3 gap-y-1">
                        <div class="text-xs sm:text-xs font-semibold text-slate-500 dark:text-slate-600 shrink-0">
                            {{ $isWholesaleApproved ? __('messages.wholesale') : __('messages.price') }}
                        </div>
                        {{-- Old price + % OFF when on sale --}}
                        <template x-if="onSale">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs sm:text-sm text-slate-600 dark:text-slate-500 line-through decoration-rose-500 decoration-2 shrink-0" x-text="fmt(baseOld)"></span>
                                <span class="inline-block text-xs sm:text-xs font-black px-2 py-0.5 rounded-full bg-rose-500 text-white shadow-sm shadow-rose-500/40" x-text="'-' + discountPct + '%'"></span>
                            </div>
                        </template>
                        <div class="text-lg sm:text-2xl lg:text-3xl font-black text-sky-700 dark:text-sky-400 font-outfit shrink-0" x-text="fmt(price)">{{ format_currency($effectivePrice, $store) }}</div>
                    </div>

                    {{-- Variant selector — grouped by attribute when available, flat pill fallback otherwise --}}
                    <template x-if="hasVariants">
                        <div class="mt-3 pt-3 border-t border-sky-200/60 dark:border-sky-900/60">
                            <template x-if="hasStructuredAttrs">
                                <div class="space-y-2.5">
                                    <template x-for="label in attrLabels" :key="label">
                                        <div>
                                            <p class="text-sm font-bold text-slate-500 dark:text-slate-600 mb-1.5" x-text="label + ':'"></p>
                                            <div class="flex flex-wrap gap-1.5">
                                                <template x-for="value in attrValues(label)" :key="label + ':' + value">
                                                    <button type="button" @click="selectAttr(label, value)"
                                                        :disabled="!isAttrAvailable(label, value)"
                                                        class="px-3 py-1.5 rounded-full text-xs sm:text-xs font-black transition border"
                                                        :class="selectedAttrs[label] === value
                                                            ? 'bg-sky-600 text-white border-sky-600 shadow-md shadow-sky-500/25'
                                                            : (isAttrAvailable(label, value)
                                                                ? 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-sky-400'
                                                                : 'bg-slate-100 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700/60 text-slate-300 dark:text-slate-600 line-through cursor-not-allowed')">
                                                        <span x-text="value"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="!hasStructuredAttrs">
                                <div>
                                    <p class="text-sm font-bold text-slate-500 dark:text-slate-600">{{ __('messages.variants') }}:</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="(v, i) in variants" :key="v.id">
                                            <button type="button" @click="selectedIndex = i"
                                                class="px-3 py-1.5 rounded-full text-xs sm:text-xs font-black transition border"
                                                :class="selectedIndex === i ? 'bg-sky-600 text-white border-sky-600 shadow-md shadow-sky-500/25' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-sky-400'">
                                                <span x-text="v.name"></span>
                                                <span class="opacity-70" x-show="(isWholesale && (v.wholesale_price || 0) > 0 ? v.wholesale_price : v.retail_price) > 0" x-text="'· ' + fmt(isWholesale && (v.wholesale_price || 0) > 0 ? v.wholesale_price : v.retail_price)"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            @if ($showRetailSale && $product->saleWindowLabel())
                                <p class="mt-2 text-xs font-bold text-rose-600 dark:text-rose-400">{{ $product->saleWindowLabel() }}</p>
                            @endif
                            <p class="mt-1.5 text-xs text-slate-600 dark:text-slate-500" x-show="!inStock">{{ __('messages.out_of_stock') }}</p>
                        </div>
                    </template>
                </div>

                @if ($product->isInStock() && ($directViberUrl || $directTelegramUrl))
                    <div class="rounded-2xl bg-white p-3 dark:bg-slate-900">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300">💬</span>
                            <div class="min-w-0">
                                <p class="text-xs font-black text-slate-900 dark:text-white">{{ __('messages.direct_order') }}</p>
                                <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-600">ပစ္စည်းအချက်အလက်ပါပြီးသား message နဲ့ ဆိုင်ကိုတိုက်ရိုက်ပို့မည်။</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            @if ($directViberUrl)
                                <a
                                    href="{{ $directViberUrl }}"
                                    :href="viberHref"
                                    :data-ios-href="viberIosHref"
                                    @click.prevent="openViberModal()"
                                    class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-purple-600 px-2 py-2 text-xs font-black text-white shadow-md shadow-purple-500/25 transition hover:bg-purple-500 active:scale-95"
                                >
                                    <x-brand-icon brand="viber" class="h-4 w-4 shrink-0"/>
                                    <span>Viber</span>
                                </a>
                            @endif
                            @if ($directTelegramUrl)
                                <a
                                    href="{{ $directTelegramUrl }}"
                                    :href="telegramHref"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-sky-500 px-2 py-2 text-xs font-black text-white shadow-md shadow-sky-500/25 transition hover:bg-sky-400 active:scale-95"
                                >
                                    <x-brand-icon brand="telegram" class="h-4 w-4 shrink-0"/>
                                    <span>Telegram</span>
                                </a>
                            @endif
                        </div>
                        @if ($directViberUrl)
                            <p class="pt-1.5 text-[11px] text-slate-400 dark:text-slate-500">
                                {{ __('messages.viber_missing') }}
                                <a href="https://www.viber.com/download/" target="_blank" rel="noopener noreferrer"
                                   class="font-bold text-sky-600 transition hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">{{ __('messages.viber_install') }} →</a>
                            </p>
                        @endif
                    </div>
                @endif

                {{-- Stock Status (reactive to selected variant) --}}
                <div class="flex flex-wrap items-center gap-1 sm:gap-3 text-xs sm:text-xs">
                    <span class="font-bold text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ __('messages.stock_status') }}:</span>
                    <template x-if="inStock">
                        <span class="px-2.5 sm:px-3 py-1 rounded-full font-bold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300/60 whitespace-nowrap">
                            {{ __('messages.in_stock') }}
                        </span>
                    </template>
                    <template x-if="!inStock">
                        <span class="px-2.5 sm:px-3 py-1 rounded-full font-bold bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 border border-rose-300/60 whitespace-nowrap">
                            {{ __('messages.out_of_stock') }}
                        </span>
                    </template>
                </div>

                {{-- Service / Digital details — shown prominently so shoppers know
                     how long a labor/service item takes or how a digital code is
                     delivered before they order. Escaped plain text only. --}}
                @php
                    $hasServiceDuration = trim((string) $product->service_duration) !== '';
                    $hasDeliveryMethod  = trim((string) $product->digital_delivery_method) !== '';
                @endphp
                @if ($hasServiceDuration || $hasDeliveryMethod)
                    <div class="space-y-2 sm:space-y-2.5 text-xs pt-0.5 sm:pt-1">
                        @if ($hasServiceDuration && $product->product_type === 'service')
                            <div class="flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5 p-2.5 sm:p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/40">
                                <span class="font-bold text-amber-900 dark:text-amber-300 text-sm shrink-0">⏱️ {{ __('messages.product_form_service_duration') }}:</span>
                                <p class="text-slate-700 dark:text-slate-300 text-xs sm:text-xs leading-relaxed">{{ $product->service_duration }}</p>
                            </div>
                        @endif
                        @if ($hasDeliveryMethod && $product->product_type === 'digital')
                            <div class="flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5 p-2.5 sm:p-3 rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-900/40">
                                <span class="font-bold text-sky-800 dark:text-sky-300 text-sm shrink-0">📲 {{ __('messages.product_form_digital_delivery_method') }}:</span>
                                <p class="text-slate-700 dark:text-slate-300 text-xs sm:text-xs leading-relaxed">{{ $product->digital_delivery_method }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Warranty & Return Policy Info — recommended order: Stock above,
                     actions below. Warranty shows inline; Return Policy is a
                     collapsible disclosure (collapsed by default so long policies
                     don't push the action buttons down on mobile). Without JS the
                     panel stays open so the policy text is never lost. Both are
                     escaped plain text — never raw HTML. --}}
                @php
                    // Whitespace-only policies count as empty — no section, no heading.
                    $hasReturnPolicy = trim((string) $product->return_policy) !== '';
                @endphp
                @if ($product->warranty || $hasReturnPolicy)
                    <div class="space-y-2 sm:space-y-2.5 text-xs pt-0.5 sm:pt-1">
                        @if ($product->warranty)
                            <div class="flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5 p-2.5 sm:p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/40">
                                <span class="font-bold text-amber-900 dark:text-amber-300 text-sm shrink-0">🛡️ {{ __('messages.warranty') }}:</span>
                                <p class="text-slate-700 dark:text-slate-300 text-xs sm:text-xs leading-relaxed">{{ $product->warranty }}</p>
                            </div>
                        @endif
                        @if ($hasReturnPolicy)
                            <div class="rounded-xl border border-sky-200/70 dark:border-sky-900/40 bg-sky-50/70 dark:bg-sky-950/40" x-data="{ open: false }">
                                <button type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'" aria-controls="return-policy-panel"
                                    class="w-full flex items-center justify-between gap-2 px-2.5 sm:px-3 py-2 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500/50 rounded-xl">
                                    <span class="font-bold text-sky-800 dark:text-sky-300 text-sm shrink-0">🔄 {{ __('messages.return_policy') }}</span>
                                    <svg class="w-4 h-4 shrink-0 text-sky-600 dark:text-sky-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div id="return-policy-panel" x-show="open" class="px-2.5 sm:px-3 pb-2.5 sm:pb-3">
                                    <p class="text-slate-700 dark:text-slate-300 text-xs sm:text-xs leading-relaxed whitespace-pre-line">{{ $product->return_policy }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Action Row — desktop inline (❤️ Favorite / Share / Add to Order); mobile uses the sticky bottom bar --}}
                <div class="hidden md:flex items-center gap-2">
                    <button
                        @click.stop.prevent="$store.favoritesStore.toggle({ id: {{ $product->id }}, name: {{ json_encode($product->name) }}, brand: {{ json_encode($product->brand?->name ?? 'General') }}, url: {{ json_encode($productUrl) }}, image_path: {{ json_encode($primaryImage ?? '') }} })"
                        type="button"
                        class="w-12 h-12 shrink-0 rounded-xl bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-lg shadow-rose-500/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-200"
                        :class="{ 'scale-110 ring-2 ring-rose-300 shadow-rose-500/50': $store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }}) }"
                        title="{{ __('messages.favorites') }}"
                        aria-label="{{ __('messages.favorites') }}"
                    >
                        <svg class="w-5 h-5 transition-transform duration-200 active:scale-125" :fill="($store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }})) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </button>

                    <x-share-button
                        :url="$productUrl"
                        :title="$product->name"
                        :text="$shareText"
                        hide-label-on-mobile
                        button-class="w-12 sm:w-22 h-12 shrink-0 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-200"
                        :show-viber="(bool) $directViberUrl"
                        :show-telegram="(bool) $directTelegramUrl"
                        :show-facebook="(bool) ($storeSetting?->facebook_url ?? '')"
                    />

                    @if ($product->isInStock())
                        <button
                            @click.prevent="$store.orderBuilder.addItem({ id: {{ $product->id }}, product_variant_id: variantId, variant_id: variantId, name: cartName, price: price, sku: sku, image_path: selectedImagePath })"
                            :disabled="!inStock"
                            type="button"
                            class="flex-1 h-12 px-4 bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 disabled:from-slate-400 disabled:to-slate-500 disabled:cursor-not-allowed text-white font-extrabold text-xs rounded-xl shadow-lg shadow-sky-500/30 transition transform active:scale-95 flex items-center justify-center gap-2"
                        >
                            <span class="text-sm">🛒</span>
                            <span>{{ __('messages.add_to_order') }}</span>
                        </button>
                    @else
                        <div class="flex-1 h-12 px-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 text-rose-700 dark:text-rose-300 font-extrabold text-xs flex items-center justify-center">
                            {{ __('messages.out_of_stock') }}
                        </div>
                    @endif
                </div>

                {{-- Single Order Form --}}
                @if ($product->isInStock())
                    <div class="space-y-2 sm:space-y-3" x-data="{ orderFormOpen: {{ ($errors->any() || old('customer_name')) ? 'true' : 'false' }}, contactChannel: '{{ old('contact_channel', 'phone') }}' }">

                        {{-- Direct Order toggle --}}
                        <button
                            type="button"
                            @click="orderFormOpen = !orderFormOpen"
                            :aria-expanded="orderFormOpen ? 'true' : 'false'"
                            class="w-full p-3.5 sm:p-4 flex items-center justify-between gap-2 transition active:scale-[0.99]"
                        >
                            <span class="font-black text-sm text-slate-900 dark:text-white font-outfit flex items-center gap-2">
                                <span>🛒</span>
                                <span>{{ __('messages.direct_order') }}</span>
                            </span>
                            <span class="flex items-center gap-1.5 shrink-0">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-500" x-text="orderFormOpen ? '{{ __('messages.close') }}' : '{{ __('messages.open') }}'"></span>
                                <svg class="w-4 h-4 text-slate-600 dark:text-slate-500 transition-transform duration-200" :class="orderFormOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </button>

                        <form
                            method="POST"
                            action="{{ url('/store/' . ($store?->slug ?? request('store_slug')) . '/orders') }}"
                            x-show="orderFormOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="w-full"
                        >
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}" />
                            <input type="hidden" name="product_variant_id" :value="variantId || ''" />

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <label class="block font-bold text-slate-800 dark:text-slate-200 mb-1">{{ __('messages.full_name') }} <span class="text-rose-500">*</span></label>
                                    <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 focus:outline-none" />
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-800 dark:text-slate-200 mb-1">{{ __('messages.phone_number') }} <span class="text-rose-500">*</span></label>
                                    <input type="tel" inputmode="tel" name="customer_phone" value="{{ old('customer_phone', auth()->user()?->phone) }}" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 focus:outline-none" />
                                </div>
                            </div>

                            <div class="grid grid-cols-6 gap-3 mt-3 text-sm">
                                <div class="col-span-2">
                                    <label class="block font-bold text-slate-800 dark:text-slate-200 mb-1">{{ __('messages.quantity') }} <span class="text-rose-500">*</span></label>
                                    <input type="number" name="quantity" value="1" min="1" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 focus:outline-none" />
                                </div>
                                <div class="col-span-4">
                                    <label class="block font-bold text-slate-800 dark:text-slate-200 mb-1">{{ __('messages.contact_channel') }}</label>
                                    <select name="contact_channel" x-model="contactChannel" x-init="$nextTick(() => { contactChannel = contactChannel || 'phone'; $el.value = contactChannel })" autocomplete="off" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 focus:outline-none">
                                        <option value="viber">Viber</option>
                                        <option value="telegram">Telegram</option>
                                        <option value="phone">{{ __('messages.phone_number') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3 text-sm">
                                <label class="block font-bold text-slate-800 dark:text-slate-200 mb-1">{{ __('messages.address') }} <span class="text-rose-500">*</span></label>
                                <textarea name="customer_address" rows="2" required placeholder="{{ __('messages.address_placeholder') }}" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 focus:outline-none"></textarea>
                            </div>

                            <div class="mt-3 text-sm" x-show="contactChannel === 'viber' || contactChannel === 'telegram'" x-transition>
                                <label class="block font-bold text-slate-800 dark:text-slate-200 mb-1">
                                    <span x-text="contactChannel === 'viber' ? 'Viber {{ __('messages.phone_number') }}' : 'Telegram @username'"></span>
                                </label>
                                <input type="text" name="contact_identifier" value="{{ old('contact_identifier') }}" :type="contactChannel === 'viber' ? 'tel' : 'text'" :inputmode="contactChannel === 'viber' ? 'tel' : 'text'" :placeholder="contactChannel === 'viber' ? '09xxxxxxxxx' : '@username'" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 px-3.5 py-2.5 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold focus:ring-2 focus:ring-sky-500 focus:outline-none" />
                            </div>

                            <button type="submit" :disabled="!inStock" class="w-full mt-3 min-h-[48px] py-3 px-4 bg-gradient-to-r from-violet-600 via-fuchsia-500 to-rose-500 hover:from-violet-600 hover:to-rose-500 disabled:from-slate-400 disabled:via-slate-500 disabled:to-slate-600 disabled:cursor-not-allowed text-white font-black text-sm rounded-2xl shadow-lg shadow-sky-500/20 transition transform active:scale-95 flex items-center justify-center gap-2">
                                <span>{{ __('messages.send_order') }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="p-3 sm:p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 text-xs text-rose-800 dark:text-rose-300 font-bold space-y-1">
                        <div class="font-bold text-xs sm:text-sm">{{ __('messages.out_of_stock') }}</div>
                        <p class="text-xs opacity-90 font-normal">{{ __('messages.out_of_stock_message') }}</p>

                        {{-- Ask about stock via Viber/Telegram --}}
                        @php
                            $askNote = 'မင်္ဂလာပါ။ ' . $product->name . ' ပစ္စည်းက လက်ကျန်ရှိပါသလား?';
                            $askViber = \App\Support\ContactLinkBuilder::viberChatUrl(
                                $store?->setting?->viber_number ?: $store?->viber_number,
                                $askNote
                            );
                            $askTelegram = \App\Support\ContactLinkBuilder::telegramUrl(
                                $store?->setting?->telegram_username ?: $store?->telegram_username,
                                $askNote
                            );
                        @endphp
                        @if ($askViber || $askTelegram)
                            <div class="pt-1.5 flex flex-wrap items-center gap-2">
                                @if ($askViber)
                                    <a href="{{ $askViber }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-purple-600 text-white font-extrabold text-xs hover:bg-purple-500 transition">
                                        <x-brand-icon brand="viber" class="h-3.5 w-3.5 inline-block -mt-0.5"/>
                                        Viber
                                    </a>
                                    <span class="text-[11px] text-slate-400 dark:text-slate-500">
                                        {{ __('messages.viber_missing') }}
                                        <a href="https://www.viber.com/download/" target="_blank" rel="noopener noreferrer"
                                           class="font-bold text-sky-600 transition hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">{{ __('messages.viber_install') }} →</a>
                                    </span>
                                @endif
                                @if ($askTelegram)
                                    <a href="{{ $askTelegram }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-sky-500 text-white font-extrabold text-xs hover:bg-sky-400 transition">
                                        <x-brand-icon brand="telegram" class="h-3.5 w-3.5 inline-block -mt-0.5"/>
                                        Telegram
                                    </a>
                                @endif
                                <span class="text-xs font-bold text-rose-700 dark:text-rose-300 opacity-80">{{ __('messages.ask_when_back_in_stock') }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Description | Specifications tabs --}}
        @php
            // Shared presenter — same mapping as the admin product form preview.
            $specRows = \App\Support\ProductSpecifications::rowsFor($product);
        @endphp
        @if (!empty($product->description) || $specRows)
            <div class="pt-4 sm:pt-6 border-t border-slate-200/60 dark:border-slate-800/60" x-data="productTabs">
                <div role="tablist" aria-label="{{ __('messages.product_details') }}" class="flex items-center gap-1 border-b border-slate-200 dark:border-slate-800">
                    <button type="button" role="tab" id="tab-description" aria-controls="panel-description"
                        :aria-selected="tab === 'description'"
                        :tabindex="tab === 'description' ? 0 : -1"
                        @click="activate('description')"
                        @keydown.arrow-right.prevent="onTabKeydown($event, 'description')"
                        @keydown.arrow-left.prevent="onTabKeydown($event, 'description')"
                        @keydown.home.prevent="onTabKeydown($event, 'description')"
                        @keydown.end.prevent="onTabKeydown($event, 'description')"
                        class="-mb-px inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-black uppercase tracking-wide transition border-b-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500/50 rounded-t-lg"
                        :class="tab === 'description' ? 'border-sky-500 text-slate-900 dark:text-white' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                        {{ __('messages.tab_description') }}
                    </button>
                    @if ($specRows)
                        <button type="button" role="tab" id="tab-specifications" aria-controls="panel-specifications"
                            :aria-selected="tab === 'specifications'"
                            :tabindex="tab === 'specifications' ? 0 : -1"
                            @click="activate('specifications')"
                            @keydown.arrow-right.prevent="onTabKeydown($event, 'specifications')"
                            @keydown.arrow-left.prevent="onTabKeydown($event, 'specifications')"
                            @keydown.home.prevent="onTabKeydown($event, 'specifications')"
                            @keydown.end.prevent="onTabKeydown($event, 'specifications')"
                            class="-mb-px inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-black uppercase tracking-wide transition border-b-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500/50 rounded-t-lg"
                            :class="tab === 'specifications' ? 'border-sky-500 text-slate-900 dark:text-white' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                            {{ __('messages.tab_specifications') }}
                        </button>
                    @endif
                </div>

                {{-- Description panel (sanitized rich text / escaped plain text) --}}
                {{-- Note: :style (not x-show) — x-show effects did not track the
                     productTabs scope when nested under the page's variants scope. --}}
                <div role="tabpanel" id="panel-description" aria-labelledby="tab-description" :style="tab === 'description' ? '' : 'display: none'" class="pt-4">
                    @if (!empty($product->description))
                        <div class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed prose prose-sm max-w-none font-myanmar">
                            {!! \App\Support\SafeHtml::sanitize($product->description) !!}
                        </div>
                    @else
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">{{ __('messages.spec_description_empty') }}</p>
                    @endif
                </div>

                {{-- Specifications panel (auto-built from real product data only) --}}
                @if ($specRows)
                    <div role="tabpanel" id="panel-specifications" aria-labelledby="tab-specifications" :style="tab === 'specifications' ? '' : 'display: none'" class="pt-4">
                        <dl class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($specRows as $spec)
                                <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,11rem)_minmax(0,1fr)] gap-x-4 gap-y-0.5 px-1 py-2.5 sm:items-start">
                                    <dt class="text-xs font-bold text-slate-500 dark:text-slate-400 break-words">{{ $spec['label'] }}</dt>
                                    <dd class="text-xs sm:text-sm font-medium text-slate-800 dark:text-slate-200 break-words min-w-0">{{ $spec['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Related Products (same category / brand) --}}
    @if ($related->count())
        <div class="pt-1 space-y-1 sm:space-y-1.5">
            <h3 class="font-black text-sm text-slate-900 dark:text-white font-outfit flex items-center gap-2">
                <span>🔗</span> <span>{{ __('messages.related_products') }}</span>
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-1 sm:gap-1.5">
                @foreach ($related as $relatedProduct)
                    <x-product-card :product="$relatedProduct" :store="$store" :isWholesaleApproved="$isWholesaleApproved ?? false" />
                @endforeach
            </div>
        </div>
    @endif

    {{-- Customer Reviews (approved only) + review form --}}
    <div class="pt-1 space-y-3 sm:space-y-4">
        <h3 class="font-black text-sm sm:text-base text-slate-900 dark:text-white font-outfit flex items-center gap-2">
            <span>⭐</span> <span>{{ __('messages.reviews') }}</span>
            @if ($avgRating)
                <span class="text-amber-500 text-xs font-black">{{ $avgRating }} ★</span>
                <span class="text-xs text-slate-600 dark:text-slate-500">({{ $reviews->count() }})</span>
            @endif
        </h3>

        @if (session('review_success'))
            <div class="rounded-2xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-xs font-bold p-3">
                ✅ {{ session('review_success') }}
            </div>
        @endif

        {{-- Review form (guest friendly) --}}
        <form method="POST" action="{{ url('/store/' . $store->slug . '/product/' . $product->slug . '/reviews') }}"
            class="w-full bg-white dark:bg-slate-900 p-4 sm:p-5 border-y border-slate-200/90 dark:border-slate-800/80 space-y-3"
            x-data="{ rating: 5 }">
            @csrf
            <p class="text-xs font-black text-slate-700 dark:text-slate-200">{{ __('messages.write_review') }}</p>
            <div class="flex items-center gap-1 text-2xl">
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button" @click="rating = {{ $i }}" :class="rating >= {{ $i }} ? 'opacity-100' : 'opacity-25'"
                        class="transition-opacity text-amber-400 hover:scale-110 active:scale-95" aria-label="{{ $i }} star">★</button>
                @endfor
                <input type="hidden" name="rating" :value="rating" value="5" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-slate-500 dark:text-slate-600 uppercase">{{ __('messages.reviewer_name') }} *</label>
                    <input type="text" name="reviewer_name" value="{{ old('reviewer_name') }}" required
                        class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                        placeholder="ဥပမာ — မောင်မောင်" />
                    @error('reviewer_name')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 dark:text-slate-600 uppercase">{{ __('messages.reviewer_phone') }}</label>
                    <input type="text" name="reviewer_phone" value="{{ old('reviewer_phone') }}"
                        class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30"
                        placeholder="09xxxxxxxxx" />
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 dark:text-slate-600 uppercase">{{ __('messages.review_comment') }}</label>
                <textarea name="comment" rows="3"
                    class="mt-1 w-full rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30">{{ old('comment') }}</textarea>
                @error('comment')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit"
                class="rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 text-white text-xs font-black px-5 py-2.5 shadow-md shadow-amber-500/30 hover:scale-[1.02] active:scale-95 transition">
                {{ __('messages.submit_review') }}
            </button>
        </form>

        {{-- Approved reviews list --}}
        <div class="space-y-2.5">
            @forelse ($reviews as $review)
                <div class="bg-white/70 dark:bg-slate-800/50 rounded-2xl p-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="w-7 h-7 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 text-white text-xs font-black flex items-center justify-center">{{ mb_substr($review->reviewer_name, 0, 1) }}</span>
                        <span class="text-xs font-extrabold text-slate-800 dark:text-slate-200">{{ $review->reviewer_name }}</span>
                        <span class="text-amber-500 text-xs">
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="{{ $i <= $review->rating ? '' : 'opacity-25' }}">★</span>
                            @endfor
                        </span>
                        <span class="ml-auto text-xs text-slate-600 dark:text-slate-500">{{ $review->created_at->format('M j, Y') }}</span>
                    </div>
                    @if ($review->comment)
                        <p class="mt-2 text-xs text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $review->comment }}</p>
                    @endif
                </div>
            @empty
                <p class="text-xs text-slate-600 dark:text-slate-500 text-center py-3">{{ __('messages.no_reviews') }}</p>
            @endforelse
        </div>
    </div>

{{-- Mobile Sticky Action Bar --}}
<div class="md:hidden fixed bottom-[100px] left-3 right-3 z-50"
    x-data="{
        dragging: false, moved: false, startX: 0, startY: 0, posX: null, posY: null,
        down(e) { this.dragging = true; this.moved = false; const t = e.touches ? e.touches[0] : e; this.startX = t.clientX; this.startY = t.clientY; },
        move(e) {
            if (!this.dragging) return;
            const t = e.touches ? e.touches[0] : e;
            if (Math.abs(t.clientX - this.startX) > 6 || Math.abs(t.clientY - this.startY) > 6) this.moved = true;
            if (!this.moved) return;
            let nx = t.clientX - this.$el.offsetWidth / 2;
            let ny = t.clientY - 20;
            nx = Math.max(8, Math.min(window.innerWidth - this.$el.offsetWidth - 8, nx));
            ny = Math.max(60, Math.min(window.innerHeight - this.$el.offsetHeight - 80, ny));
            this.$el.style.left = nx + 'px'; this.$el.style.top = ny + 'px';
            this.$el.style.right = 'auto'; this.$el.style.bottom = 'auto';
            this.posX = nx; this.posY = ny;
            if (e.touches) e.preventDefault();
        },
        up() {
            if (!this.dragging) return;
            this.dragging = false;
            if (this.moved && this.posX !== null) {
                localStorage.setItem('productBarPos', JSON.stringify({ x: this.posX, y: this.posY }));
            }
        },
        initBar() {
            const s = localStorage.getItem('productBarPos');
            if (s) { try { const p = JSON.parse(s); if (p.x !== null) { this.$el.style.left = p.x + 'px'; this.$el.style.top = p.y + 'px'; this.$el.style.right = 'auto'; this.$el.style.bottom = 'auto'; } } catch(e){} }
        }
    }"
    x-init="initBar()"
    @touchstart.passive="down($event)" @touchmove.prevent="move($event)" @touchend="up()"
    @mousedown="down($event)" @mousemove="dragging && move($event)" @mouseup="up()" @mouseleave="dragging && up()"
    :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
    :style="moved ? 'user-select:none' : ''"
>
    <div class="rounded-2xl bg-white dark:bg-slate-900 shadow-2xl px-2 py-2.5 flex items-center gap-2">
    {{-- Favorite --}}
    <button
        @click.stop.prevent="$store.favoritesStore.toggle({ id: {{ $product->id }}, name: {{ json_encode($product->name) }}, brand: {{ json_encode($product->brand?->name ?? 'General') }}, url: {{ json_encode($productUrl) }}, image_path: {{ json_encode($primaryImage ?? '') }} })"
        type="button"
        class="w-12 h-12 shrink-0 rounded-xl bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-lg shadow-rose-500/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-200"
        :class="{ 'scale-110 ring-2 ring-rose-300 shadow-rose-500/50': $store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }}) }"
        title="{{ __('messages.favorites') }}"
        aria-label="{{ __('messages.favorites') }}"
    >
        <svg class="w-5 h-5 transition-transform duration-200 active:scale-125" :fill="($store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }})) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
    </button>

    <x-share-button
        :url="$productUrl"
        :title="$product->name"
        :text="$shareText"
        hide-label-on-mobile
        button-class="w-12 sm:w-22 h-12 shrink-0 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all duration-200"
        :show-viber="(bool) $directViberUrl"
        :show-telegram="(bool) $directTelegramUrl"
        :show-facebook="(bool) ($storeSetting?->facebook_url ?? '')"
    />

    @if ($product->isInStock())
        <button
            @click.prevent="$store.orderBuilder.addItem({ id: {{ $product->id }}, product_variant_id: variantId, variant_id: variantId, name: cartName, price: price, sku: sku, image_path: selectedImagePath })"
            :disabled="!inStock"
            type="button"
            class="relative flex-1 h-12 px-4 bg-gradient-to-r from-violet-600 to-rose-500 hover:from-violet-600 hover:to-rose-500 disabled:from-slate-400 disabled:to-slate-500 disabled:cursor-not-allowed text-white font-black text-sm rounded-xl shadow-lg shadow-violet-500/30 transition active:scale-95 flex items-center justify-center gap-2"
        >
            <span class="text-base">🛒</span>
            <span class="truncate">{{ __('messages.add_to_order') }}</span>
            <span x-show="$store.orderBuilder && $store.orderBuilder.getVariantQty({{ $product->id }}, variantId) > 0" class="absolute -top-1.5 -right-1.5 min-w-[22px] h-[22px] px-1 rounded-full bg-white text-violet-600 font-black text-xs flex items-center justify-center border-2 border-violet-600 shadow-md" x-text="$store.orderBuilder ? $store.orderBuilder.getVariantQty({{ $product->id }}, variantId) : 0"></span>
        </button>
    @else
        <div class="flex-1 h-12 px-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 text-rose-700 dark:text-rose-300 font-black text-sm flex items-center justify-center">
            {{ __('messages.out_of_stock') }}
        </div>
    @endif
</div>

</div>
@endsection

@once
@include('storefront.components._viber_order_modal')
@endonce
