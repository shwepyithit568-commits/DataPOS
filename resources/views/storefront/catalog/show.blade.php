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
    $storeLogo = $storeSetting?->storefrontLogo() ?: $storeSetting?->adminLogo();
    $storeLogoUrl = $storeLogo ? asset('storage/' . $storeLogo) : null;
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
    $storeViberNumber = $storeSetting?->viber_number ? preg_replace('/[^\d+]/', '', $storeSetting->viber_number) : null;
    $storeTelegramHandle = $storeSetting?->telegram_username ? ltrim($storeSetting->telegram_username, '@') : null;
    $storeViberUrl = $storeViberNumber ? "viber://chat?number={$storeViberNumber}" : null;
    $storeTelegramUrl = $storeTelegramHandle ? "https://t.me/{$storeTelegramHandle}" : null;
    // Normalized channel targets — reused client-side so the Direct Order links
    // rebuild the message (with the selected variant) as the shopper switches.
    $directViberNumber = \App\Support\ContactLinkBuilder::normalizeMyanmarPhone($storeSetting?->viber_number);
    $directTelegramUser = \App\Support\ContactLinkBuilder::telegramUsername($storeSetting?->telegram_username);
    $shareText = $product->name . ' — ' . format_currency($effectivePrice, $store) . ' — ' . ($store?->name ?? config('app.name'));
@endphp

<div class="max-w-7xl mx-auto space-y-0.5 sm:space-y-1 pb-[80px] md:pb-10"
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

    {{-- Breadcrumbs & Back Navigation (Lazada Style) --}}
    <div class="flex items-center justify-between gap-2 px-1 py-1 text-xs">
        <nav aria-label="Breadcrumb" class="flex items-center gap-1.5 font-medium text-slate-500 dark:text-slate-400 overflow-x-auto scrollbar-none py-1 min-w-0">
            <a href="{{ $homeUrl ?? url('/?store_slug=' . $storeSlug) }}" class="inline-flex items-center gap-1 hover:text-orange-600 dark:hover:text-orange-400 transition shrink-0">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>{{ __('messages.home') }}</span>
            </a>
            <span class="text-slate-300 dark:text-slate-600 shrink-0">/</span>
            <a href="{{ url('/products?store_slug=' . $storeSlug) }}" class="hover:text-orange-600 dark:hover:text-orange-400 transition shrink-0">
                {{ __('messages.products') }}
            </a>
            @if ($product->category)
                <span class="text-slate-300 dark:text-slate-600 shrink-0">/</span>
                <a href="{{ url('/products?category_id=' . $product->category->id . '&store_slug=' . $storeSlug) }}" class="hover:text-orange-600 dark:hover:text-orange-400 transition shrink-0 max-w-[150px] truncate">
                    {{ $product->category->name }}
                </a>
            @endif
            <span class="text-slate-300 dark:text-slate-600 shrink-0">/</span>
            <span class="text-slate-700 dark:text-slate-200 font-bold truncate max-w-[180px] sm:max-w-xs">{{ $product->name }}</span>
        </nav>
        <a href="{{ url()->previous() }}" class="sf-btn-3d px-3 py-1 text-xs font-bold shrink-0 cursor-pointer">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span>{{ __('messages.back') }}</span>
        </a>
    </div>

    {{-- Main Product Detail Card (Lazada White Container) --}}
    <div class="w-full rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm p-4 sm:p-6 lg:p-7 space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
            
            {{-- Left Column: Interactive Image Gallery (5 cols) --}}
            <div class="lg:col-span-5 space-y-3.5" x-data="{
                isZoomed: false,
                originX: '50%',
                originY: '50%',
                handleMouseMove(e) {
                    const rect = e.currentTarget.getBoundingClientRect();
                    const x = ((e.clientX - rect.left) / rect.width) * 100;
                    const y = ((e.clientY - rect.top) / rect.height) * 100;
                    this.originX = Math.max(0, Math.min(100, x)).toFixed(1) + '%';
                    this.originY = Math.max(0, Math.min(100, y)).toFixed(1) + '%';
                },
                handleTouchMove(e) {
                    if (!this.isZoomed || !e.touches || e.touches.length === 0) return;
                    const rect = e.currentTarget.getBoundingClientRect();
                    const t = e.touches[0];
                    const x = ((t.clientX - rect.left) / rect.width) * 100;
                    const y = ((t.clientY - rect.top) / rect.height) * 100;
                    this.originX = Math.max(0, Math.min(100, x)).toFixed(1) + '%';
                    this.originY = Math.max(0, Math.min(100, y)).toFixed(1) + '%';
                },
                toggleTouchZoom(e) {
                    this.isZoomed = !this.isZoomed;
                    if (this.isZoomed) {
                        this.handleTouchMove(e);
                    }
                }
            }">
                {{-- Main Active Image Hero Box (Full-width in-frame zoom) --}}
                <div 
                    class="relative overflow-hidden rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 aspect-square w-full p-2 flex items-center justify-center group shadow-sm select-none cursor-zoom-in"
                    @mouseenter="isZoomed = true"
                    @mouseleave="isZoomed = false"
                    @mousemove="handleMouseMove($event)"
                    @click="isZoomed = !isZoomed"
                    @touchmove="handleTouchMove($event)"
                >
                    {{-- Store Logo / Official Tag Badge --}}
                    <div class="absolute top-3 left-3 z-10 flex flex-col gap-1.5 pointer-events-none">
                        @if ($storeLogoUrl)
                            <span class="inline-flex items-center px-2 py-1 rounded-lg bg-white/95 dark:bg-slate-900/95 backdrop-blur-xs border border-slate-200/90 dark:border-slate-700 shadow-sm max-h-7">
                                <img src="{{ $storeLogoUrl }}" alt="{{ $store?->name }}" class="h-4 max-w-[80px] object-contain" />
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-gradient-to-b from-sky-400 via-sky-500 to-sky-600 border border-sky-200 border-b-2 border-b-sky-800 text-white font-black text-[11px] tracking-wide uppercase shadow-xs">
                                ★ {{ $store?->name ?? 'Official' }}
                            </span>
                        @endif
                        <template x-if="onSale">
                            <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded bg-rose-500 text-white font-black text-[11px] shadow-xs w-fit">
                                <span x-text="'-' + discountPct + '%'"></span>
                            </span>
                        </template>
                    </div>

                    {{-- Stock Warning Badge --}}
                    <template x-if="!inStock">
                        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-xs z-10 flex items-center justify-center pointer-events-none">
                            <span class="px-4 py-1.5 rounded-full bg-rose-600 text-white font-black text-xs shadow-lg uppercase tracking-wider">
                                {{ __('messages.out_of_stock') }}
                            </span>
                        </div>
                    </template>

                    {{-- In-Frame Zoom Status Pill --}}
                    <div class="absolute bottom-3 right-3 z-10 pointer-events-none transition-all duration-200">
                        <span 
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-white font-bold text-xs shadow-md backdrop-blur-md transition-colors"
                            :class="isZoomed ? 'bg-orange-600/90' : 'bg-slate-900/70'"
                        >
                            <span x-text="isZoomed ? '🔍 Zoom Out' : '🔍 Zoom In'"></span>
                        </span>
                    </div>

                    {{-- Inner Zoom Image Container --}}
                    <div 
                        class="w-full h-full flex items-center justify-center transition-transform duration-200 ease-out will-change-transform"
                        :style="isZoomed ? `transform: scale(2.2); transform-origin: ${originX} ${originY};` : 'transform: scale(1); transform-origin: center center;'"
                    >
                        <template x-if="activeImage">
                            <img 
                                :src="'/storage/' + activeImage" 
                                alt="{{ $product->name }}" 
                                class="w-full h-full object-contain rounded-xl transition-opacity duration-200 pointer-events-none"
                                data-img-fallback="hide-next"
                            />
                        </template>
                        <x-product-image 
                            ::path="activeImage" 
                            :alt="$product->name" 
                            class="w-full h-full object-contain pointer-events-none"
                            aspect="aspect-square" 
                        />
                    </div>
                </div>

                {{-- Thumbnail Strip Navigation --}}
                @if (count($gallery) > 1)
                    <div x-data="{ isDown: false, startX: 0, scrollLeft: 0 }" @mousedown="isDown = true; startX = $event.pageX - $el.offsetLeft; scrollLeft = $el.scrollLeft" @mouseleave="isDown = false" @mouseup="isDown = false" @mousemove="if(isDown){$event.preventDefault();const x=$event.pageX-$el.offsetLeft;const walk=(x-startX)*1.5;$el.scrollLeft=scrollLeft-walk}" class="flex items-center justify-start gap-2.5 overflow-x-auto pb-1 scrollbar-thin cursor-grab active:cursor-grabbing select-none w-full">
                        @foreach ($gallery as $img)
                            <button 
                                @click="activeImage = '{{ $img }}'" 
                                type="button" 
                                class="w-16 h-16 sm:w-18 sm:h-18 rounded-xl overflow-hidden border-2 transition-all duration-150 shrink-0 focus:outline-none"
                                :class="activeImage === '{{ $img }}' ? 'border-orange-500 ring-2 ring-orange-500/20 scale-102 shadow-xs' : 'border-slate-200 dark:border-slate-700 opacity-60 hover:opacity-100 hover:border-slate-400'"
                            >
                                <img src="{{ asset('storage/' . $img) }}" alt="{{ __('messages.thumbnail') }}" class="w-full h-full object-cover" />
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Trust & Service Badges (Enhanced with bespoke icons) --}}
                <div class="grid grid-cols-2 gap-2.5 pt-2 text-xs text-slate-700 dark:text-slate-300 w-full border-t border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-orange-100 dark:bg-orange-950/70 text-orange-600 dark:text-orange-400 flex items-center justify-center text-xs shrink-0 shadow-2xs">
                            🛡️
                        </span>
                        <span class="font-semibold text-[11px] sm:text-xs truncate">100% Authentic</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-emerald-100 dark:bg-emerald-950/70 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xs shrink-0 shadow-2xs">
                            💵
                        </span>
                        <span class="font-semibold text-[11px] sm:text-xs truncate">Cash On Delivery</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-sky-100 dark:bg-sky-950/70 text-sky-600 dark:text-sky-400 flex items-center justify-center text-xs shrink-0 shadow-2xs">
                            🚚
                        </span>
                        <span class="font-semibold text-[11px] sm:text-xs truncate">Fast Delivery</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-purple-100 dark:bg-purple-950/70 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xs shrink-0 shadow-2xs">
                            ⭐
                        </span>
                        <span class="font-semibold text-[11px] sm:text-xs truncate">Service Guaranteed</span>
                    </div>
                </div>
            </div>

            {{-- Right Column: Buy Box & Product Info (7.5 cols) --}}
            <div class="lg:col-span-7 space-y-4">
                
                {{-- Product Title & Header --}}
                <div class="space-y-2">
                    <div class="flex items-center gap-2 flex-wrap text-xs">
                        @if ($storeLogoUrl)
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200/90 dark:border-slate-700 shadow-2xs max-h-6">
                                <img src="{{ $storeLogoUrl }}" alt="{{ $store?->name }}" class="h-3.5 max-w-[80px] object-contain" />
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-gradient-to-b from-sky-400 via-sky-500 to-sky-600 border border-sky-200 border-b-2 border-b-sky-800 text-white font-black text-[10px] tracking-wide uppercase shadow-xs">
                                ★ {{ $store?->name ?? 'Official' }}
                            </span>
                        @endif
                        @if ($product->brand)
                            <a href="{{ url('/products?brand_id=' . $product->brand->id . '&store_slug=' . $storeSlug) }}" class="font-bold text-sky-600 dark:text-sky-400 hover:underline">
                                {{ __('messages.brand') ?? 'Brand' }}: {{ $product->brand->name }}
                            </a>
                        @endif
                        <span class="text-slate-300 dark:text-slate-600">|</span>
                        <span class="text-slate-500 dark:text-slate-400 font-mono">
                            {{ __('messages.sku') }}: <span x-text="sku">{{ $product->sku }}</span>
                        </span>
                    </div>

                    <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-slate-900 dark:text-white leading-snug">
                        {{ $product->name }}
                    </h1>

                    {{-- Ratings & Reviews Summary Bar --}}
                    <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 pt-0.5 border-b border-slate-100 dark:border-slate-800 pb-2">
                        <div class="flex items-center gap-1 text-amber-500">
                            <span class="font-bold text-slate-900 dark:text-white">{{ $avgRating ?: '5.0' }}</span>
                            <div class="flex text-xs">
                                @for ($i = 1; $i <= 5; $i++)
                                    <span class="{{ $i <= ($avgRating ?: 5) ? 'text-amber-400' : 'text-slate-300 dark:text-slate-600' }}">★</span>
                                @endfor
                            </div>
                        </div>
                        <span>·</span>
                        <a href="#panel-reviews" class="text-sky-600 dark:text-sky-400 hover:underline">
                            {{ $reviews->count() }} {{ __('messages.reviews') }}
                        </a>
                        <span>·</span>
                        <template x-if="inStock">
                            <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                {{ __('messages.in_stock') }}
                            </span>
                        </template>
                        <template x-if="!inStock">
                            <span class="text-rose-600 dark:text-rose-400 font-bold">
                                {{ __('messages.out_of_stock') }}
                            </span>
                        </template>

                        {{-- Share / Wishlist Quick Actions (Right-aligned with 3D tactile push buttons) --}}
                        <div class="ml-auto flex items-center gap-2">
                            {{-- Favorite Heart Button (3D Metallic Gold / Rose Tactile Push Button) --}}
                            <button
                                @click.stop.prevent="$store.favoritesStore.toggle({ id: {{ $product->id }}, name: {{ json_encode($product->name) }}, brand: {{ json_encode($product->brand?->name ?? 'General') }}, url: {{ json_encode($productUrl) }}, image_path: {{ json_encode($primaryImage ?? '') }} })"
                                type="button"
                                class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl font-black text-xs transition-all duration-150 transform hover:-translate-y-0.5 active:translate-y-0.5 select-none cursor-pointer ring-1 ring-white/60"
                                :class="$store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }}) 
                                    ? 'bg-gradient-to-b from-rose-500 via-rose-600 to-rose-700 border border-rose-300 border-b-[3px] border-b-rose-900 shadow-md shadow-rose-950/40 text-white' 
                                    : 'bg-gradient-to-b from-amber-300 via-amber-400 to-amber-600 border border-amber-200 border-b-[3px] border-b-amber-700 shadow-md shadow-amber-900/30 text-slate-900'"
                                title="{{ __('messages.favorites') }}"
                            >
                                <svg class="w-4 h-4 shrink-0 transition-transform drop-shadow-xs" :class="($store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }})) ? 'text-white' : 'text-slate-900'" :fill="($store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }})) ? 'currentColor' : 'currentColor'" viewBox="0 0 24 24">
                                    <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.684a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span class="hidden sm:inline" :class="($store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }})) ? 'text-white' : 'text-slate-900'" x-text="($store.favoritesStore && $store.favoritesStore.isFav({{ $product->id }})) ? 'သိမ်းဆည်းပြီး' : 'သိမ်းမည်'"></span>
                            </button>

                            {{-- Share Button (3D Vibrant Sky Blue Tactile Button) --}}
                            <x-share-button
                                :url="$productUrl"
                                :title="$product->name"
                                :text="$shareText"
                                hide-label-on-mobile
                                button-class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-b from-sky-400 via-sky-500 to-sky-600 border border-sky-200 border-b-[3px] border-b-sky-800 text-white text-xs font-black shadow-md shadow-sky-950/30 transition transform hover:-translate-y-0.5 active:translate-y-0.5 cursor-pointer ring-1 ring-white/40"
                                :show-viber="(bool) $directViberUrl"
                                :show-telegram="(bool) $directTelegramUrl"
                                :show-facebook="(bool) ($storeSetting?->facebook_url ?? '')"
                            />
                        </div>
                    </div>
                </div>

                {{-- Lazada Signature Price Box with 3D Wholesale & Promo Badges --}}
                <div class="p-4 rounded-2xl bg-orange-50/70 dark:bg-slate-800/80 border border-orange-200/90 dark:border-slate-700/80 shadow-xs space-y-2">
                    <div class="flex items-baseline gap-3 flex-wrap">
                        <div class="text-2xl sm:text-3xl lg:text-4xl font-black text-[#f85606] dark:text-orange-400 font-outfit" x-text="fmt(price)">
                            {{ format_currency($effectivePrice, $store) }}
                        </div>
                        <template x-if="onSale">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-slate-400 line-through decoration-slate-400 font-mono" x-text="fmt(baseOld)"></span>
                                <span class="text-xs font-black text-white bg-gradient-to-b from-rose-500 to-rose-600 border border-rose-300 border-b-2 border-b-rose-800 px-2 py-0.5 rounded-lg shadow-xs" x-text="'-' + discountPct + '%'"></span>
                            </div>
                        </template>
                        @if ($isWholesaleApproved)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-gradient-to-b from-emerald-500 to-emerald-600 border border-emerald-300 border-b-2 border-b-emerald-800 text-white font-black text-xs shadow-xs uppercase tracking-wide">
                                💼 {{ __('messages.wholesale') }}
                            </span>
                        @endif
                    </div>

                    {{-- Promotion Tag & Perks --}}
                    <div class="flex items-center gap-2 text-xs pt-1 flex-wrap">
                        <span class="px-2.5 py-0.5 rounded-lg bg-gradient-to-b from-amber-500 to-orange-500 border border-amber-300 border-b-2 border-b-orange-700 text-white font-black text-[10px] shadow-2xs uppercase tracking-wider">
                            ⚡ PROMOTION
                        </span>
                        <span class="text-slate-700 dark:text-slate-300 font-bold">
                            {{ $isWholesaleApproved ? __('messages.wholesale') : 'Best Price Guarantee' }}
                        </span>
                        @if ($showRetailSale && $product->saleWindowLabel())
                            <span class="text-rose-600 dark:text-rose-400 font-black ml-auto">{{ $product->saleWindowLabel() }}</span>
                        @endif
                    </div>
                </div>

                {{-- Delivery & Service Options (Lazada Delivery Card) --}}
                <div class="rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-800/40 p-3.5 space-y-2.5 text-xs">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                            <span class="text-slate-400">🚚</span>
                            <span class="font-bold">Standard Delivery:</span>
                            <span class="text-slate-500 dark:text-slate-400">Estimated 1 - 3 Days</span>
                        </div>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">Available</span>
                    </div>

                    <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                        <span class="text-slate-400">💵</span>
                        <span class="font-bold">Cash on Delivery (COD):</span>
                        <span class="text-slate-500 dark:text-slate-400">Supported</span>
                    </div>

                    @if ($product->warranty)
                        <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
                            <span class="text-slate-400">🛡️</span>
                            <span class="font-bold">{{ __('messages.warranty') }}:</span>
                            <span class="text-slate-600 dark:text-slate-300 font-semibold">{{ $product->warranty }}</span>
                        </div>
                    @endif

                    @php
                        $hasReturnPolicy = trim((string) $product->return_policy) !== '';
                    @endphp
                    @if ($hasReturnPolicy)
                        <div class="rounded-lg border border-slate-200/70 dark:border-slate-700/60 bg-white/60 dark:bg-slate-800/60 overflow-hidden" x-data="{ open: false }">
                            <button type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'" aria-controls="return-policy-panel"
                                class="w-full flex items-center justify-between gap-2 px-2.5 py-1.5 text-left focus:outline-none rounded-lg">
                                <span class="font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                    <span>🔄</span> <span>{{ __('messages.return_policy') }}</span>
                                </span>
                                <svg class="w-3.5 h-3.5 shrink-0 text-slate-400 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div id="return-policy-panel" x-show="open" class="px-2.5 pb-2 pt-0.5 border-t border-slate-100 dark:border-slate-700/50">
                                <p class="text-slate-600 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $product->return_policy }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Variant Selector --}}
                <template x-if="hasVariants">
                    <div class="space-y-3 pt-1">
                        <template x-if="hasStructuredAttrs">
                            <div class="space-y-2.5">
                                <template x-for="label in attrLabels" :key="label">
                                    <div>
                                        <p class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5" x-text="label + ':'"></p>
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="value in attrValues(label)" :key="label + ':' + value">
                                                <button type="button" @click="selectAttr(label, value)"
                                                    :disabled="!isAttrAvailable(label, value)"
                                                    class="sf-btn-3d px-3.5 py-1.5 rounded-xl text-xs font-bold cursor-pointer"
                                                    :class="selectedAttrs[label] === value
                                                        ? 'sf-btn-3d-orange'
                                                        : (isAttrAvailable(label, value)
                                                            ? ''
                                                            : 'opacity-40 line-through cursor-not-allowed')">
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
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">{{ __('messages.variants') }}:</p>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="(v, i) in variants" :key="v.id">
                                        <button type="button" @click="selectedIndex = i"
                                            class="sf-btn-3d px-3.5 py-1.5 rounded-xl text-xs font-bold cursor-pointer"
                                            :class="selectedIndex === i ? 'sf-btn-3d-orange' : ''">
                                            <span x-text="v.name"></span>
                                            <span class="opacity-70 font-mono" x-show="(isWholesale && (v.wholesale_price || 0) > 0 ? v.wholesale_price : v.retail_price) > 0" x-text="'· ' + fmt(isWholesale && (v.wholesale_price || 0) > 0 ? v.wholesale_price : v.retail_price)"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Action Buttons Row (Lazada Signature Dual 3D Tactile Buttons: Buy Now + Add to Cart) --}}
                <div class="flex items-center gap-2.5 sm:gap-3 pt-2">
                    @if ($product->isInStock())
                        {{-- Buy Now (Primary 3D Orange Push Button) --}}
                        <button
                            @click.prevent="$store.orderBuilder.addItem({ id: {{ $product->id }}, product_variant_id: variantId, variant_id: variantId, name: cartName, price: price, sku: sku, image_path: selectedImagePath }); window.location.href='{{ url('/order-builder?store_slug=' . $storeSlug) }}'"
                            :disabled="!inStock"
                            type="button"
                            class="sf-btn-3d-orange flex-1 h-12 px-4 sm:px-6 disabled:opacity-50 text-white font-black text-xs sm:text-sm rounded-xl flex items-center justify-center gap-2 cursor-pointer select-none"
                        >
                            <span class="text-base">⚡</span>
                            <span class="font-black text-white">{{ __('messages.buy_now') }}</span>
                        </button>

                        {{-- Add to Cart / Order (Secondary 3D Primary Dynamic Sky/Brand Button) --}}
                        <button
                            @click.prevent="$store.orderBuilder.addItem({ id: {{ $product->id }}, product_variant_id: variantId, variant_id: variantId, name: cartName, price: price, sku: sku, image_path: selectedImagePath })"
                            :disabled="!inStock"
                            type="button"
                            class="sf-btn-3d-primary flex-1 h-12 px-4 sm:px-6 disabled:opacity-50 text-white font-black text-xs sm:text-sm rounded-xl flex items-center justify-center gap-2 cursor-pointer select-none"
                        >
                            <span class="text-base">🛒</span>
                            <span class="font-black text-white">{{ __('messages.add_to_order') }}</span>
                        </button>
                    @else
                        <div class="w-full space-y-2.5">
                            <div class="w-full h-12 px-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 text-rose-700 dark:text-rose-300 font-black text-sm flex items-center justify-center">
                                {{ __('messages.out_of_stock') }}
                            </div>
                            @if ($storeViberUrl || $storeTelegramUrl)
                                <div class="rounded-xl border border-slate-200/90 dark:border-slate-700/80 bg-slate-50/80 dark:bg-slate-800/60 p-3 space-y-2">
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('messages.ask_when_back_in_stock') }}</p>
                                    <div class="grid {{ ($storeViberUrl && $storeTelegramUrl) ? 'grid-cols-2' : 'grid-cols-1' }} gap-2">
                                        @if ($storeViberUrl)
                                            <a href="{{ $storeViberUrl }}" target="_blank" rel="noopener noreferrer"
                                               class="sf-btn-3d-viber inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl px-3 py-2 text-xs font-black text-white cursor-pointer select-none">
                                                <x-brand-icon brand="viber" class="h-4 w-4 shrink-0 text-white fill-white"/>
                                                <span class="font-black text-white">{{ __('messages.open_viber') }}</span>
                                            </a>
                                        @endif
                                        @if ($storeTelegramUrl)
                                            <a href="{{ $storeTelegramUrl }}" target="_blank" rel="noopener noreferrer"
                                               class="sf-btn-3d-telegram inline-flex min-h-[42px] items-center justify-center gap-2 rounded-xl px-3 py-2 text-xs font-black text-white cursor-pointer select-none">
                                                <x-brand-icon brand="telegram" class="h-4 w-4 shrink-0 text-white fill-white"/>
                                                <span class="font-black text-white">Telegram</span>
                                            </a>
                                        @endif
                                    </div>
                                    @if ($storeViberUrl)
                                        <p class="pt-0.5 text-[11px] font-medium text-slate-600 dark:text-slate-300 flex items-center gap-1.5 flex-wrap">
                                            <span>{{ __('messages.viber_missing') }}</span>
                                            <a href="https://www.viber.com/download/" target="_blank" rel="noopener noreferrer"
                                               class="font-black text-[#7360F2] hover:underline dark:text-violet-400">
                                                {{ __('messages.viber_install') }} →
                                            </a>
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Direct Order Box (Viber & Telegram 3D Push Action Buttons) --}}
                @if ($product->isInStock() && ($directViberUrl || $directTelegramUrl))
                    <div class="rounded-2xl border border-purple-200/80 dark:border-slate-700/80 bg-purple-50/40 dark:bg-slate-800/60 p-3.5 space-y-3 shadow-2xs">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-black text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span>💬</span>
                                <span>{{ __('messages.direct_order') }} (Chat)</span>
                            </span>
                        </div>
                        <div class="grid {{ ($directViberUrl && $directTelegramUrl) ? 'grid-cols-2' : 'grid-cols-1' }} gap-2.5">
                            @if ($directViberUrl)
                                <a
                                    href="{{ $directViberUrl }}"
                                    :href="viberHref"
                                    :data-ios-href="viberIosHref"
                                    @click.prevent="openViberModal()"
                                    class="sf-btn-3d-viber inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-black text-white cursor-pointer select-none"
                                >
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-white/20 shrink-0">
                                        <x-brand-icon brand="viber" class="h-3.5 w-3.5 shrink-0 fill-white text-white"/>
                                    </span>
                                    <span class="font-black text-white">{{ __('messages.open_viber') }}</span>
                                </a>
                            @endif
                            @if ($directTelegramUrl)
                                <a
                                    href="{{ $directTelegramUrl }}"
                                    :href="telegramHref"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="sf-btn-3d-telegram inline-flex min-h-[44px] items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm font-black text-white cursor-pointer select-none"
                                >
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-white/20 shrink-0">
                                        <x-brand-icon brand="telegram" class="h-3.5 w-3.5 shrink-0 fill-white text-white"/>
                                    </span>
                                    <span class="font-black text-white">Telegram Chat</span>
                                </a>
                            @endif
                        </div>
                        @if ($directViberUrl)
                            <p class="pt-0.5 text-[11px] font-medium text-slate-600 dark:text-slate-300 flex items-center gap-1.5 flex-wrap">
                                <span>{{ __('messages.viber_missing') }}</span>
                                <a href="https://www.viber.com/download/" target="_blank" rel="noopener noreferrer"
                                   class="font-black text-[#7360F2] hover:underline dark:text-violet-400">
                                    {{ __('messages.viber_install') }} →
                                </a>
                            </p>
                        @endif
                    </div>
                @endif

                {{-- Collapsible Direct Order Form --}}
                @if ($product->isInStock())
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-800/40 overflow-hidden" x-data="{ orderFormOpen: {{ ($errors->any() || old('customer_name')) ? 'true' : 'false' }}, contactChannel: '{{ old('contact_channel', 'phone') }}' }">
                        <button
                            type="button"
                            @click="orderFormOpen = !orderFormOpen"
                            :aria-expanded="orderFormOpen ? 'true' : 'false'"
                            class="w-full p-3 flex items-center justify-between gap-2 transition hover:bg-slate-100/80 dark:hover:bg-slate-800/60 cursor-pointer"
                        >
                            <span class="font-bold text-xs text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                                <span>📝</span>
                                <span>{{ __('messages.direct_order') }} (အမြန်မှာယူရန်)</span>
                            </span>
                            <span class="flex items-center gap-1 text-xs text-[#f85606] dark:text-orange-400 font-bold">
                                <span x-text="orderFormOpen ? '{{ __('messages.close') }}' : '{{ __('messages.open') }}'"></span>
                                <svg class="w-4 h-4 transition-transform duration-150" :class="orderFormOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </button>

                        <form
                            method="POST"
                            action="{{ url('/store/' . ($store?->slug ?? request('store_slug')) . '/orders') }}"
                            x-show="orderFormOpen"
                            x-cloak
                            class="p-3.5 pt-0 border-t border-slate-200/60 dark:border-slate-700/60"
                        >
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}" />
                            <input type="hidden" name="product_variant_id" :value="variantId || ''" />

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs pt-3">
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.full_name') }} <span class="text-rose-500">*</span></label>
                                    <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-orange-500 focus:outline-none" />
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.phone_number') }} <span class="text-rose-500">*</span></label>
                                    <input type="tel" inputmode="tel" name="customer_phone" value="{{ old('customer_phone', auth()->user()?->phone) }}" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-orange-500 focus:outline-none" />
                                </div>
                            </div>

                            <div class="grid grid-cols-6 gap-3 mt-3 text-xs">
                                <div class="col-span-2">
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.quantity') }} <span class="text-rose-500">*</span></label>
                                    <input type="number" name="quantity" value="1" min="1" required class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-orange-500 focus:outline-none" />
                                </div>
                                <div class="col-span-4">
                                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.contact_channel') }}</label>
                                    <select name="contact_channel" x-model="contactChannel" x-init="$nextTick(() => { contactChannel = contactChannel || 'phone'; $el.value = contactChannel })" autocomplete="off" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-orange-500 focus:outline-none">
                                        <option value="viber">Viber</option>
                                        <option value="telegram">Telegram</option>
                                        <option value="phone">{{ __('messages.phone_number') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3 text-xs">
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.address') }} <span class="text-rose-500">*</span></label>
                                <textarea name="customer_address" rows="2" required placeholder="{{ __('messages.address_placeholder') }}" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-orange-500 focus:outline-none"></textarea>
                            </div>

                            <div class="mt-3 text-xs" x-show="contactChannel === 'viber' || contactChannel === 'telegram'" x-transition>
                                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">
                                    <span x-text="contactChannel === 'viber' ? 'Viber {{ __('messages.phone_number') }}' : 'Telegram @username'"></span>
                                </label>
                                <input type="text" name="contact_identifier" value="{{ old('contact_identifier') }}" :type="contactChannel === 'viber' ? 'tel' : 'text'" :inputmode="contactChannel === 'viber' ? 'tel' : 'text'" :placeholder="contactChannel === 'viber' ? '09xxxxxxxxx' : '@username'" class="w-full rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-orange-500 focus:outline-none" />
                            </div>

                            <button type="submit" :disabled="!inStock" class="sf-btn-3d-orange w-full mt-3 min-h-[44px] py-2.5 px-4 disabled:opacity-50 text-white font-black text-xs rounded-xl flex items-center justify-center gap-2 cursor-pointer">
                                <span class="font-black text-white">{{ __('messages.send_order') }}</span>
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        {{-- Description & Specifications Tabs (Lazada Style) --}}
        @php
            $specRows = \App\Support\ProductSpecifications::rowsFor($product);
        @endphp
        @if (!empty($product->description) || $specRows)
            <div class="pt-4 border-t border-slate-200/80 dark:border-slate-800" x-data="productTabs">
                <div role="tablist" aria-label="{{ __('messages.product_details') }}" class="flex items-center gap-4 border-b border-slate-200 dark:border-slate-800">
                    <button type="button" role="tab" id="tab-description" aria-controls="panel-description"
                        :aria-selected="tab === 'description'"
                        :tabindex="tab === 'description' ? 0 : -1"
                        @click="activate('description')"
                        @keydown.arrow-right.prevent="onTabKeydown($event, 'description')"
                        @keydown.arrow-left.prevent="onTabKeydown($event, 'description')"
                        @keydown.home.prevent="onTabKeydown($event, 'description')"
                        @keydown.end.prevent="onTabKeydown($event, 'description')"
                        class="-mb-px inline-flex items-center gap-1.5 pb-3 text-xs sm:text-sm font-bold tracking-wide transition border-b-2 focus:outline-none"
                        :class="tab === 'description' ? 'border-[#f85606] text-[#f85606]' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
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
                            class="-mb-px inline-flex items-center gap-1.5 pb-3 text-xs sm:text-sm font-bold tracking-wide transition border-b-2 focus:outline-none"
                            :class="tab === 'specifications' ? 'border-[#f85606] text-[#f85606]' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                            {{ __('messages.tab_specifications') }}
                        </button>
                    @endif
                </div>

                {{-- Description panel --}}
                <div role="tabpanel" id="panel-description" aria-labelledby="tab-description" :style="tab === 'description' ? '' : 'display: none'" class="pt-4">
                    @if (!empty($product->description))
                        <div class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed prose prose-sm max-w-none font-myanmar">
                            {!! \App\Support\SafeHtml::sanitize($product->description) !!}
                        </div>
                    @else
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 leading-relaxed">{{ __('messages.spec_description_empty') }}</p>
                    @endif
                </div>

                {{-- Specifications panel --}}
                @if ($specRows)
                    <div role="tabpanel" id="panel-specifications" aria-labelledby="tab-specifications" :style="tab === 'specifications' ? '' : 'display: none'" class="pt-4">
                        <div class="rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                            <dl class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($specRows as $spec)
                                    <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,12rem)_minmax(0,1fr)] gap-x-4 gap-y-1 px-4 py-2.5 sm:items-center bg-white dark:bg-slate-900 odd:bg-slate-50/50 dark:odd:bg-slate-800/30">
                                        <dt class="text-xs font-bold text-slate-500 dark:text-slate-400 break-words">{{ $spec['label'] }}</dt>
                                        <dd class="text-xs sm:text-sm font-semibold text-slate-900 dark:text-slate-100 break-words min-w-0">{{ $spec['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Related Products Section (Lazada Style Related / Recommended Products) --}}
    @if (isset($related) && $related->isNotEmpty())
        <div class="w-full rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm p-4 sm:p-6 space-y-4">
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-5 rounded-full bg-gradient-to-b from-[#f85606] to-red-600"></span>
                    <h3 class="font-bold text-base sm:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <span>{{ __('messages.related_products') ?? 'ဆက်စပ် ကုန်ပစ္စည်းများ' }}</span>
                    </h3>
                </div>
                @if ($product->category)
                    <a href="{{ url('/products?category_id=' . $product->category_id . '&store_slug=' . $storeSlug) }}" class="sf-btn-3d px-3 py-1 text-xs font-bold shrink-0 cursor-pointer">
                        <span>{{ __('messages.view_all') ?? 'အားလုံးကြည့်ရန်' }}</span>
                        <span class="ml-1">→</span>
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4">
                @foreach ($related as $relProduct)
                    <x-product-card 
                        :product="$relProduct" 
                        :store="$store" 
                        :isWholesaleApproved="$isWholesaleApproved" 
                    />
                @endforeach
            </div>
        </div>
    @endif

    {{-- Ratings & Reviews (Lazada Style Breakdown Card) --}}
    <div id="panel-reviews" class="w-full rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 shadow-sm p-4 sm:p-6 space-y-4">
        <h3 class="font-bold text-base text-slate-900 dark:text-white flex items-center gap-2">
            <span>Ratings & Reviews</span>
        </h3>

        {{-- Overview Card (Rating Score + Star Bars) --}}
        <div class="p-4 rounded-xl bg-orange-50/40 dark:bg-slate-800/40 border border-orange-100/60 dark:border-slate-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="text-center">
                    <div class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white font-outfit">{{ $avgRating ?: '5.0' }}<span class="text-lg text-slate-400">/5</span></div>
                    <div class="flex items-center justify-center text-amber-400 text-sm">
                        @for ($i = 1; $i <= 5; $i++)
                            <span>★</span>
                        @endfor
                    </div>
                    <p class="text-[11px] text-slate-500 mt-0.5">{{ $reviews->count() }} Ratings</p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap text-xs">
                <span class="px-3 py-1 rounded-full bg-orange-500 text-white font-bold">All ({{ $reviews->count() }})</span>
                <span class="px-3 py-1 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-medium">5 Star ({{ $reviews->where('rating', 5)->count() }})</span>
                <span class="px-3 py-1 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-medium">With Comments</span>
            </div>
        </div>

        @if (session('review_success'))
            <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-xs font-bold p-3">
                ✅ {{ session('review_success') }}
            </div>
        @endif

        {{-- Review Write Form --}}
        <form method="POST" action="{{ url('/store/' . $store->slug . '/product/' . $product->slug . '/reviews') }}"
            class="rounded-xl bg-slate-50/70 dark:bg-slate-800/40 p-4 border border-slate-200/80 dark:border-slate-800 space-y-3"
            x-data="{ rating: 5 }">
            @csrf
            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ __('messages.write_review') }}</p>
            <div class="flex items-center gap-1 text-2xl">
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button" @click="rating = {{ $i }}" :class="rating >= {{ $i }} ? 'opacity-100 scale-105' : 'opacity-25'"
                        class="transition-all duration-150 text-amber-400 hover:scale-110 active:scale-95" aria-label="{{ $i }} star">★</button>
                @endfor
                <input type="hidden" name="rating" :value="rating" value="5" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">{{ __('messages.reviewer_name') }} *</label>
                    <input type="text" name="reviewer_name" value="{{ old('reviewer_name') }}" required
                        class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 focus:border-orange-500 focus:outline-none"
                        placeholder="ဥပမာ — မောင်မောင်" />
                    @error('reviewer_name')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">{{ __('messages.reviewer_phone') }}</label>
                    <input type="text" name="reviewer_phone" value="{{ old('reviewer_phone') }}"
                        class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 focus:border-orange-500 focus:outline-none"
                        placeholder="09xxxxxxxxx" />
                </div>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase">{{ __('messages.review_comment') }}</label>
                <textarea name="comment" rows="2"
                    class="mt-1 w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-bold text-slate-900 dark:text-slate-100 focus:border-orange-500 focus:outline-none">{{ old('comment') }}</textarea>
                @error('comment')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit"
                class="sf-btn-3d-orange rounded-xl px-5 py-2.5 text-xs font-black text-white cursor-pointer">
                <span class="font-black text-white">{{ __('messages.submit_review') }}</span>
            </button>
        </form>

        {{-- Reviews List --}}
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($reviews as $review)
                <div class="py-3.5 space-y-1.5">
                    <div class="flex items-center gap-2">
                        <div class="flex text-amber-400 text-xs">
                            @for ($i = 1; $i <= 5; $i++)
                                <span class="{{ $i <= $review->rating ? '' : 'opacity-25' }}">★</span>
                            @endfor
                        </div>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">by {{ $review->reviewer_name }}</span>
                        <span class="text-[11px] text-emerald-600 font-semibold">✓ Verified Purchase</span>
                        <span class="ml-auto text-[11px] text-slate-400">{{ $review->created_at->format('d M Y') }}</span>
                    </div>
                    @if ($review->comment)
                        <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed">{{ $review->comment }}</p>
                    @endif
                </div>
            @empty
                <p class="text-xs text-slate-400 text-center py-4">{{ __('messages.no_reviews') }}</p>
            @endforelse
        </div>
    </div>

    </div>
</div>
</div>
@endsection

@once
@include('storefront.components._viber_order_modal')
@endonce

