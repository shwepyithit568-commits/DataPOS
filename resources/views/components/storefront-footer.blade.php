@props([
    'setting' => null,          // StorefrontSetting model (preferred source)
    'store' => null,            // Store model — slug + structured payment/delivery methods
    'storeDisplayName' => null, // falls back to setting->store_name / store->name / app name
    'storeSlug' => null,        // active store slug (for ?store_slug= URLs)
])

@php
    $store = $store ?? app(\App\Services\StoreContext::class)->getStore();
    $setting = $setting ?? $store?->setting;
    $storeSlug = $storeSlug ?? request('store_slug') ?? $store?->slug;
    $storeDisplayName = $storeDisplayName ?? ($setting?->store_name ?? $store?->name ?? config('app.name'));

    // URLs
    $homeUrl = $storeSlug ? url('/?store_slug=' . $storeSlug) : url('/');
    $productsUrl = $storeSlug ? url('/products?store_slug=' . $storeSlug) : url('/products');
    $glassFinderUrl = $storeSlug ? url('/glass-finder?store_slug=' . $storeSlug) : url('/glass-finder');
    $serviceTrackingUrl = $storeSlug ? url('/service-tracking?store_slug=' . $storeSlug) : url('/service-tracking');
    $howToOrderUrl = $storeSlug ? url('/how-to-order?store_slug=' . $storeSlug) : url('/how-to-order');
    $blogUrl = $storeSlug ? url('/blog?store_slug=' . $storeSlug) : url('/blog');
    $accountUrl = $storeSlug ? url('/account?store_slug=' . $storeSlug) : url('/account');

    // Admin Contact & Social Media
    $phone = trim((string) ($setting?->phone ?? $store?->phone ?? ''));
    $address = trim((string) ($setting?->address ?? $store?->address ?? ''));
    $openingHours = trim((string) ($setting?->opening_hours ?? ''));

    $ftViberUrl = \App\Support\ContactLinkBuilder::viberChatUrl($setting?->viber_number ?? $phone);
    $ftViberIosUrl = \App\Support\ContactLinkBuilder::viberIosContactUrl($setting?->viber_number ?? $phone);
    $ftTelegramUrl = \App\Support\ContactLinkBuilder::telegramUrl($setting?->telegram_username);

    $ftSocialLinks = [
        'facebook' => trim((string) ($setting?->facebook_url ?? '')),
        'youtube'  => trim((string) ($setting?->youtube_url ?? '')),
        'tiktok'   => trim((string) ($setting?->tiktok_url ?? '')),
    ];
    $ftSocialLinks = array_filter($ftSocialLinks, fn($url) => preg_match('#^https?://#i', $url));

    // Admin Dynamic Payment & Delivery Methods
    $ftPaymentMethods = $store?->paymentMethods()->active()->orderBy('sort_order')->get() ?? collect();
    $ftDeliveryMethods = $store?->deliveryMethods()->active()->orderBy('sort_order')->get() ?? collect();

    // Map Locations & Fallbacks
    $ftMapUrl = $setting?->mapUrl();
    $ftMapDirectionsUrl = $setting?->mapDirectionsUrl();

    // High-Contrast Typography standard classes
    $colHeader = 'text-[13px] sm:text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-white font-myanmar border-b-2 border-orange-500 pb-1 inline-block';
    $linkStyle = 'text-xs sm:text-[13px] font-semibold text-slate-700 hover:text-orange-600 dark:text-slate-300 dark:hover:text-orange-400 transition-colors font-myanmar block py-1';
@endphp

<footer class="w-full bg-slate-100 text-slate-800 dark:bg-slate-950 dark:text-slate-200 border-t border-slate-300 dark:border-slate-800 transition-colors font-sans mt-10">

    {{-- ================= 1. ALIEXPRESS-STYLE VALUE & SERVICE GUARANTEE STRIP ================= --}}
    <div class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-5 sm:py-6">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">

                {{-- Guarantee 1: Value & Pricing --}}
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600 dark:bg-orange-950/60 dark:text-orange-400 border border-orange-300 dark:border-orange-800 text-lg">
                        🏷️
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white font-myanmar leading-tight">{{ __('messages.great_value_title') }}</h4>
                        <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 font-myanmar truncate">{{ __('messages.great_value_desc') }}</p>
                    </div>
                </div>

                {{-- Guarantee 2: Nationwide Delivery --}}
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-300 dark:border-blue-800 text-lg">
                        🚚
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white font-myanmar leading-tight">{{ __('messages.nationwide_delivery_title') }}</h4>
                        <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 font-myanmar truncate">{{ __('messages.nationwide_delivery_desc') }}</p>
                    </div>
                </div>

                {{-- Guarantee 3: Safe Payment & Integrity --}}
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800 text-lg">
                        🛡️
                    </div>
                    <div class="min-w-0">
                        <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white font-myanmar leading-tight">{{ __('messages.safe_warranty_title') }}</h4>
                        <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 font-myanmar truncate">{{ __('messages.safe_warranty_desc') }}</p>
                    </div>
                </div>

                {{-- Guarantee 4: Live Help & Service Tracking --}}
                @if (store_can('service.repair_jobs', $store))
                    <a href="{{ $serviceTrackingUrl }}" class="flex items-center gap-3 group">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400 border border-purple-300 dark:border-purple-800 text-lg group-hover:scale-105 transition-transform">
                            🔧
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white font-myanmar leading-tight group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors">{{ __('messages.nav_service_track') }}</h4>
                                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            </div>
                            <p class="text-xs font-semibold text-violet-700 dark:text-violet-400 font-myanmar truncate">{{ __('messages.service_tracking_sub') }}</p>
                        </div>
                    </a>
                @endif

            </div>
        </div>
    </div>

    {{-- ================= 2. MAIN 4-COLUMN HIGH CONTRAST SECTION (CARDS ON MOBILE) ================= --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <div class="grid grid-cols-1 gap-3.5 sm:gap-6 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Column 1: Customer Service / အကူအညီနှင့် ဝန်ဆောင်မှု --}}
            <div class="rounded-2xl border border-slate-200/90 dark:border-slate-800/90 bg-white dark:bg-slate-900 p-4 sm:p-0 sm:bg-transparent sm:dark:bg-transparent sm:border-0 shadow-2xs sm:shadow-none space-y-3">
                <div>
                    <h3 class="{{ $colHeader }}">
                        {{ __('messages.customer_service') }}
                    </h3>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-1 gap-2 sm:gap-1.5 pt-1">
                    @if (store_can('storefront.online_ordering', $store))
                        <a href="{{ $howToOrderUrl }}" class="flex items-center gap-1.5 p-2 sm:p-0 rounded-xl bg-slate-50 sm:bg-transparent dark:bg-slate-800/50 sm:dark:bg-transparent border border-slate-200/60 sm:border-0 dark:border-slate-700/60 {{ $linkStyle }}">
                            <span>📖</span>
                            <span>{{ __('messages.how_to_order') }}</span>
                        </a>
                    @endif
                    @if (store_can('service.repair_jobs', $store))
                        <a href="{{ $serviceTrackingUrl }}" class="flex items-center gap-1.5 p-2 sm:p-0 rounded-xl bg-violet-50/60 sm:bg-transparent dark:bg-violet-950/30 sm:dark:bg-transparent border border-violet-200/60 sm:border-0 dark:border-violet-800/60 {{ $linkStyle }} font-bold text-violet-700 dark:text-violet-400 hover:text-violet-900">
                            <span>🔧</span>
                            <span>{{ __('messages.nav_service_track') }}</span>
                        </a>
                    @endif
                    @if (store_can('storefront.glass_finder', $store))
                        <a href="{{ $glassFinderUrl }}" class="flex items-center gap-1.5 p-2 sm:p-0 rounded-xl bg-slate-50 sm:bg-transparent dark:bg-slate-800/50 sm:dark:bg-transparent border border-slate-200/60 sm:border-0 dark:border-slate-700/60 {{ $linkStyle }}">
                            <span>📱</span>
                            <span>{{ __('messages.glass_finder') }}</span>
                        </a>
                    @endif
                    <a href="{{ $productsUrl }}" class="flex items-center gap-1.5 p-2 sm:p-0 rounded-xl bg-slate-50 sm:bg-transparent dark:bg-slate-800/50 sm:dark:bg-transparent border border-slate-200/60 sm:border-0 dark:border-slate-700/60 {{ $linkStyle }}">
                        <span>🛍️</span>
                        <span>{{ __('messages.products') }}</span>
                    </a>
                    @if (store_can('storefront.customer_portal', $store))
                        <a href="{{ $accountUrl }}" class="flex items-center gap-1.5 p-2 sm:p-0 rounded-xl bg-slate-50 sm:bg-transparent dark:bg-slate-800/50 sm:dark:bg-transparent border border-slate-200/60 sm:border-0 dark:border-slate-700/60 {{ $linkStyle }}">
                            <span>👤</span>
                            <span>{{ __('messages.account') }}</span>
                        </a>
                    @endif
                    @if (store_can('storefront.blog', $store))
                        <a href="{{ $blogUrl }}" class="flex items-center gap-1.5 p-2 sm:p-0 rounded-xl bg-slate-50 sm:bg-transparent dark:bg-slate-800/50 sm:dark:bg-transparent border border-slate-200/60 sm:border-0 dark:border-slate-700/60 {{ $linkStyle }}">
                            <span>📰</span>
                            <span>{{ __('messages.blog') }}</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Column 2: Shopping & Category Guide / ဆိုင်အချက်အလက် --}}
            <div class="rounded-2xl border border-slate-200/90 dark:border-slate-800/90 bg-white dark:bg-slate-900 p-4 sm:p-0 sm:bg-transparent sm:dark:bg-transparent sm:border-0 shadow-2xs sm:shadow-none space-y-3">
                <div>
                    <h3 class="{{ $colHeader }}">
                        ဆိုင်အချက်အလက်
                    </h3>
                </div>
                <div class="space-y-2.5 text-xs sm:text-[13px] text-slate-800 dark:text-slate-200 font-myanmar pt-1">
                    <p class="leading-relaxed font-medium">
                        @if ($setting?->tagline)
                            {{ $setting->tagline }}
                        @else
                            {{ __('messages.store_about') }}
                        @endif
                    </p>

                    @if ($openingHours)
                        <div class="pt-2 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2">
                            <span class="font-extrabold text-slate-900 dark:text-white block text-xs">{{ __('messages.opening_hours') }}:</span>
                            <span class="text-slate-800 dark:text-slate-300 font-semibold px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-[11px]">⏰ {{ $openingHours }}</span>
                        </div>
                    @endif

                    <div class="pt-1">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300 text-xs font-bold border border-emerald-200 dark:border-emerald-800 w-full justify-center sm:w-auto">
                            <span>✓</span>
                            <span>တရားဝင် အသိအမှတ်ပြု အရောင်းဆိုင်</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Column 3: Contact Channels / ဆက်သွယ်ရန် --}}
            <div class="rounded-2xl border border-slate-200/90 dark:border-slate-800/90 bg-white dark:bg-slate-900 p-4 sm:p-0 sm:bg-transparent sm:dark:bg-transparent sm:border-0 shadow-2xs sm:shadow-none space-y-3">
                <div>
                    <h3 class="{{ $colHeader }}">
                        {{ __('messages.contact') }}
                    </h3>
                </div>
                <div class="space-y-2.5 text-xs sm:text-[13px] font-myanmar pt-1">
                    @if ($phone)
                        <a href="tel:{{ $phone }}" class="flex items-center justify-between p-2.5 rounded-xl bg-orange-50/60 hover:bg-orange-100/80 dark:bg-orange-950/30 dark:hover:bg-orange-950/50 border border-orange-200/80 dark:border-orange-800/60 transition group">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-7 h-7 rounded-lg bg-orange-500 text-white flex items-center justify-center text-xs shrink-0 shadow-2xs">📞</span>
                                <div>
                                    <span class="text-[10px] font-bold text-orange-800 dark:text-orange-300 block">Hotline Phone</span>
                                    <span class="font-mono font-black text-slate-950 dark:text-white text-xs tracking-wide">{{ $phone }}</span>
                                </div>
                            </div>
                            <span class="text-xs text-orange-600 dark:text-orange-400 font-bold group-hover:translate-x-0.5 transition-transform">Call →</span>
                        </a>
                    @endif

                    @if ($ftViberUrl || $ftTelegramUrl)
                        <div class="pt-0.5">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-300 block mb-1.5">တိုက်ရိုက်စကားပြောရန် (Direct Chat):</span>
                            <div class="grid grid-cols-2 gap-2">
                                @if ($ftViberUrl)
                                    <a href="{{ $ftViberUrl }}" data-ios-href="{{ $ftViberIosUrl ?? $ftViberUrl }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-violet-300 bg-violet-50/70 p-2 text-xs font-bold text-violet-800 hover:bg-violet-100 dark:border-violet-700 dark:bg-violet-950/40 dark:text-violet-300 shadow-2xs transition active:scale-95">
                                        <x-brand-icon brand="viber" class="h-3.5 w-3.5 fill-current"/>
                                        <span>Viber</span>
                                    </a>
                                @endif
                                @if ($ftTelegramUrl)
                                    <a href="{{ $ftTelegramUrl }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-sky-300 bg-sky-50/70 p-2 text-xs font-bold text-sky-800 hover:bg-sky-100 dark:border-sky-700 dark:bg-sky-950/40 dark:text-sky-300 shadow-2xs transition active:scale-95">
                                        <x-brand-icon brand="telegram" class="h-3.5 w-3.5 fill-current"/>
                                        <span>Telegram</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($address)
                        <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200/70 dark:border-slate-800">
                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 block uppercase">ဆိုင်လိပ်စာ</span>
                            <p class="text-slate-850 dark:text-slate-200 leading-relaxed text-xs font-medium pt-0.5">
                                📍 {{ $address }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Column 4: Store Branding, Location & Socials --}}
            <div class="rounded-2xl border border-slate-200/90 dark:border-slate-800/90 bg-white dark:bg-slate-900 p-4 sm:p-0 sm:bg-transparent sm:dark:bg-transparent sm:border-0 shadow-2xs sm:shadow-none space-y-3">
                {{-- Store Name / Logo (on top) --}}
                <div>
                    <a href="{{ $homeUrl }}" class="inline-flex items-center gap-2">
                        @if (!empty(($setting ?? null)?->storefrontLogo()))
                            <img src="{{ asset('storage/' . $setting->storefrontLogo()) }}" alt="{{ $storeDisplayName }}"
                                 class="h-9 w-auto max-w-[14rem] object-contain" loading="lazy" decoding="async" />
                        @else
                            <span class="font-outfit text-base sm:text-lg font-black text-slate-950 dark:text-white">{{ $storeDisplayName }}</span>
                        @endif
                    </a>
                </div>

                <div class="space-y-3 pt-0.5">
                    {{-- Action Row: Share Button and Get Directions --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <x-share-button
                            :url="$homeUrl"
                            :title="$storeDisplayName"
                            :label="__('messages.share')"
                            button-class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 text-xs font-bold text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 shadow-2xs cursor-pointer active:scale-95 transition"
                            :show-viber="(bool) $ftViberUrl"
                            :show-telegram="(bool) $ftTelegramUrl"
                            :show-facebook="!empty($ftSocialLinks['facebook'])"
                        />

                        @if ($ftMapDirectionsUrl)
                            <a href="{{ $ftMapDirectionsUrl }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-blue-50/80 px-3 text-xs font-bold text-blue-700 border border-blue-200 hover:bg-blue-100 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300 shadow-2xs transition active:scale-95">
                                <span>🧭 {{ __('messages.get_directions') }}</span>
                            </a>
                        @endif
                    </div>

                    {{-- Social Media Links --}}
                    @if (!empty($ftSocialLinks['facebook']) || !empty($ftSocialLinks['youtube']) || !empty($ftSocialLinks['tiktok']))
                        <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-2">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-300 block">
                                {{ __('messages.follow_us') }}:
                            </span>
                            <div class="flex flex-wrap items-center gap-2">
                                @if (!empty($ftSocialLinks['facebook']))
                                    <a href="{{ $ftSocialLinks['facebook'] }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-300 bg-white text-blue-600 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-800 dark:text-blue-400 shadow-2xs transition-colors"
                                       title="Facebook" aria-label="Facebook">
                                        <x-brand-icon brand="facebook" class="h-3.5 w-3.5 fill-current"/>
                                    </a>
                                @endif
                                @if (!empty($ftSocialLinks['youtube']))
                                    <a href="{{ $ftSocialLinks['youtube'] }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-300 bg-white text-red-600 hover:bg-red-50 dark:border-slate-700 dark:bg-slate-800 dark:text-red-400 shadow-2xs transition-colors"
                                       title="YouTube" aria-label="YouTube">
                                        <x-brand-icon brand="youtube" class="h-3.5 w-3.5 fill-current"/>
                                    </a>
                                @endif
                                @if (!empty($ftSocialLinks['tiktok']))
                                    <a href="{{ $ftSocialLinks['tiktok'] }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-900 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white shadow-2xs transition-colors"
                                       title="TikTok" aria-label="TikTok">
                                        <x-brand-icon brand="tiktok" class="h-3.5 w-3.5 fill-current"/>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Mini Google Map Display (at the bottom) --}}
                    @if (!empty($setting?->map_enabled) && !empty($setting?->map_embed_enabled) && $setting?->mapEmbedSrc())
                        <div class="pt-2 border-t border-slate-200 dark:border-slate-800 space-y-1.5">
                            <div class="relative w-full h-28 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-2xs">
                                <iframe class="w-full h-full border-0"
                                        src="{{ $setting->mapEmbedSrc() }}"
                                        title="{{ $setting->map_title ?: $storeDisplayName }}"
                                        loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade"
                                        allowfullscreen>
                                </iframe>
                            </div>
                            @if ($ftMapUrl)
                                <a href="{{ $ftMapUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline">
                                    <span>🗺️ {{ __('messages.view_on_map') }} →</span>
                                </a>
                            @endif
                        </div>
                    @elseif (!empty($setting?->map_enabled) && $ftMapUrl)
                        <div class="pt-2 border-t border-slate-200 dark:border-slate-800">
                            <a href="{{ $ftMapUrl }}" target="_blank" rel="noopener noreferrer"
                               class="flex items-center justify-between p-2.5 rounded-xl bg-rose-50/70 hover:bg-rose-100/90 dark:bg-rose-950/40 dark:hover:bg-rose-950/60 border border-rose-200/80 dark:border-rose-900/50 transition group shadow-2xs">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-7 h-7 rounded-lg bg-rose-500 text-white flex items-center justify-center text-xs shrink-0 shadow-2xs group-hover:scale-105 transition-transform">🗺️</span>
                                    <div class="min-w-0">
                                        <span class="text-[11px] font-bold text-rose-900 dark:text-rose-200 block truncate">Google Map တည်နေရာ</span>
                                        <span class="text-[10px] text-rose-700 dark:text-rose-400 block truncate">{{ __('messages.view_on_map') }}</span>
                                    </div>
                                </div>
                                <span class="text-xs text-rose-600 dark:text-rose-400 font-bold group-hover:translate-x-0.5 transition-transform">→</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- ================= 3. PAYMENT & DELIVERY PARTNERS STRIP (HIGH CONTRAST & CENTER-ALIGNED) ================= --}}
    <div class="border-t border-slate-200 dark:border-slate-800/80 bg-white dark:bg-slate-900/60 py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-4">
            
            {{-- Pay With Row (Centered) --}}
            <div class="flex flex-col md:flex-row items-center justify-center gap-2.5 sm:gap-4 text-center">
                <span class="text-xs sm:text-[13px] font-extrabold text-slate-900 dark:text-white shrink-0 font-myanmar uppercase tracking-wide flex items-center justify-center gap-1.5">
                    <span>💳</span>
                    <span>{{ __('messages.payment') }}:</span>
                </span>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    @if ($ftPaymentMethods->isNotEmpty())
                        @foreach ($ftPaymentMethods as $pm)
                            <button type="button"
                                @click="$dispatch('open-payment-modal', {
                                    name: @js($pm->name),
                                    qr_url: @js($pm->qrUrl()),
                                    account_name: @js($pm->show_account_details ? $pm->account_name : null),
                                    account_number: @js($pm->show_account_details ? $pm->account_number : null),
                                    instructions: @js($pm->instructions),
                                })"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-extrabold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs hover:border-violet-400 dark:hover:border-violet-500 hover:shadow-xs transition active:scale-95 cursor-pointer">
                                <x-payment-method-icon :method="$pm" class="h-3.5 w-3.5" text-class="text-[7px]" />
                                <span>{{ $pm->name }}</span>
                                @if ($pm->hasQr())
                                    <span class="text-[10px] text-emerald-600 dark:text-emerald-400" title="QR Code ပါရှိသည်">📱</span>
                                @endif
                            </button>
                        @endforeach
                    @else
                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">KBZPay</span>
                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">WavePay</span>
                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">AYA / CB</span>
                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">Cash on Delivery</span>
                    @endif

                    @if ($setting?->payment_info)
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 font-myanmar">
                            ({{ $setting->payment_info }})
                        </span>
                    @endif
                </div>
            </div>

            {{-- Delivery Partners Row (Centered) --}}
            <div class="flex flex-col md:flex-row items-center justify-center gap-2.5 sm:gap-4 text-center pt-3 border-t border-slate-200/70 dark:border-slate-800/70">
                <span class="text-xs sm:text-[13px] font-extrabold text-slate-900 dark:text-white shrink-0 font-myanmar uppercase tracking-wide flex items-center justify-center gap-1.5">
                    <span>🚚</span>
                    <span>{{ __('messages.delivery') }}:</span>
                </span>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    @if ($ftDeliveryMethods->isNotEmpty())
                        @foreach ($ftDeliveryMethods as $dm)
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-extrabold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">
                                <span>{{ $dm->icon ?: '🚚' }}</span>
                                <span>{{ $dm->name }}</span>
                                @if ($dm->estimated_time)
                                    <span class="text-[11px] text-slate-600 dark:text-slate-400 font-semibold">({{ $dm->estimated_time }})</span>
                                @endif
                            </span>
                        @endforeach
                    @else
                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">🛵 အိမ်အရောက် Express</span>
                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">📦 ကားဂိတ် အမြန်ချော</span>
                    @endif

                    @if ($setting?->delivery_info)
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 font-myanmar">
                            ({{ $setting->delivery_info }})
                        </span>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- ================= 4. BOTTOM COPYRIGHT & BACK-TO-TOP (CENTERED ROW) ================= --}}
    <div class="border-t border-slate-300 bg-slate-900 text-slate-100 dark:border-slate-800 dark:bg-black py-4">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-6 text-xs font-myanmar text-center">
            
            {{-- Copyright and footer ad --}}
            <div class="flex flex-wrap items-center justify-center gap-2 text-center">
                <span class="font-black text-white text-[13px]">{{ $storeDisplayName }}</span>
                <span class="text-slate-400">·</span>
                <span class="text-slate-100 font-medium">
                    @if (!empty($setting?->footer_ad_text))
                        {{ $setting->footer_ad_text }}
                    @else
                        © {{ date('Y') }} DataPOS. All rights reserved.
                    @endif
                </span>
            </div>

            {{-- High contrast Back-to-top button --}}
            <button
                type="button"
                @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-slate-800 text-white border border-slate-600 hover:bg-orange-600 hover:border-orange-500 transition-colors text-xs font-black cursor-pointer shadow-xs shrink-0"
                title="{{ __('messages.back_to_top') }}"
            >
                <span>↑ {{ __('messages.back_to_top') }}</span>
            </button>
        </div>
    </div>

    {{-- Payment QR Code & Details Interactive Modal --}}
    <x-payment-qr-modal />

</footer>
