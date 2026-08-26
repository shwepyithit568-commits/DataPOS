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

            </div>
        </div>
    </div>

    {{-- ================= 2. MAIN 4-COLUMN HIGH CONTRAST SECTION ================= --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Column 1: Customer Service / အကူအညီနှင့် ဝန်ဆောင်မှု --}}
            <div class="space-y-3">
                <div>
                    <h3 class="{{ $colHeader }}">
                        {{ __('messages.customer_service') }}
                    </h3>
                </div>
                <ul class="space-y-1.5 pt-1">
                    <li><a href="{{ $howToOrderUrl }}" class="{{ $linkStyle }}">📖 {{ __('messages.how_to_order') }}</a></li>
                    <li><a href="{{ $serviceTrackingUrl }}" class="{{ $linkStyle }} font-bold text-violet-700 dark:text-violet-400 hover:text-violet-900">🔧 {{ __('messages.nav_service_track') }} (Live)</a></li>
                    <li><a href="{{ $glassFinderUrl }}" class="{{ $linkStyle }}">📱 {{ __('messages.glass_finder') }}</a></li>
                    <li><a href="{{ $productsUrl }}" class="{{ $linkStyle }}">🛍️ {{ __('messages.products') }}</a></li>
                    <li><a href="{{ $accountUrl }}" class="{{ $linkStyle }}">👤 {{ __('messages.account') }}</a></li>
                    <li><a href="{{ $blogUrl }}" class="{{ $linkStyle }}">📰 {{ __('messages.blog') }}</a></li>
                </ul>
            </div>

            {{-- Column 2: Shopping & Category Guide / ဆိုင်အချက်အလက် --}}
            <div class="space-y-3">
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
                        <div class="pt-2 border-t border-slate-300 dark:border-slate-800">
                            <span class="font-extrabold text-slate-900 dark:text-white block text-xs">{{ __('messages.opening_hours') }}:</span>
                            <span class="text-slate-800 dark:text-slate-300 font-semibold">⏰ {{ $openingHours }}</span>
                        </div>
                    @endif

                    <div class="pt-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-100 text-emerald-900 dark:bg-emerald-950/80 dark:text-emerald-300 text-xs font-bold border border-emerald-300 dark:border-emerald-800">
                            <span>✓</span>
                            <span>တရားဝင် အသိအမှတ်ပြု အရောင်းဆိုင်</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Column 3: Contact Channels / ဆက်သွယ်ရန် --}}
            <div class="space-y-3">
                <div>
                    <h3 class="{{ $colHeader }}">
                        {{ __('messages.contact') }}
                    </h3>
                </div>
                <div class="space-y-2.5 text-xs sm:text-[13px] font-myanmar pt-1">
                    @if ($phone)
                        <div>
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-300 block">ဖုန်းနံပါတ် (Hotline):</span>
                            <a href="tel:{{ $phone }}" class="font-mono font-black text-slate-950 dark:text-white hover:text-orange-600 dark:hover:text-orange-400 text-sm tracking-wide inline-block pt-0.5">
                                📞 {{ $phone }}
                            </a>
                        </div>
                    @endif

                    @if ($ftViberUrl || $ftTelegramUrl)
                        <div class="pt-1">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-300 block mb-1.5">တိုက်ရိုက်စကားပြောရန်:</span>
                            <div class="flex flex-wrap gap-1.5">
                                @if ($ftViberUrl)
                                    <a href="{{ $ftViberUrl }}" data-ios-href="{{ $ftViberIosUrl ?? $ftViberUrl }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1.5 rounded-md border border-violet-400 bg-white px-2.5 py-1 text-xs font-bold text-violet-800 hover:bg-violet-50 dark:border-violet-700 dark:bg-slate-900 dark:text-violet-300 shadow-2xs">
                                        <x-brand-icon brand="viber" class="h-3.5 w-3.5 fill-current"/>
                                        <span>Viber</span>
                                    </a>
                                @endif
                                @if ($ftTelegramUrl)
                                    <a href="{{ $ftTelegramUrl }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1.5 rounded-md border border-sky-400 bg-white px-2.5 py-1 text-xs font-bold text-sky-800 hover:bg-sky-50 dark:border-sky-700 dark:bg-slate-900 dark:text-sky-300 shadow-2xs">
                                        <x-brand-icon brand="telegram" class="h-3.5 w-3.5 fill-current"/>
                                        <span>Telegram</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($address)
                        <div class="pt-1">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-300 block">ဆိုင်လိပ်စာ:</span>
                            <p class="text-slate-800 dark:text-slate-200 leading-relaxed text-xs font-medium pt-0.5">
                                📍 {{ $address }}
                            </p>
                        </div>
                    @endif

                    @if ($ftMapUrl || $ftMapDirectionsUrl)
                        <div class="flex flex-wrap gap-1.5 pt-1.5">
                            @if ($ftMapUrl)
                                <a href="{{ $ftMapUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1 rounded-md bg-white px-2.5 py-1 text-xs font-bold text-rose-700 border border-slate-300 hover:bg-rose-50 dark:border-slate-700 dark:bg-slate-900 dark:text-rose-400 shadow-2xs">
                                    <span>🗺️ {{ __('messages.view_on_map') }}</span>
                                </a>
                            @endif
                            @if ($ftMapDirectionsUrl)
                                <a href="{{ $ftMapDirectionsUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1 rounded-md bg-white px-2.5 py-1 text-xs font-bold text-blue-700 border border-slate-300 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-900 dark:text-blue-400 shadow-2xs">
                                    <span>🧭 {{ __('messages.get_directions') }}</span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Column 4: Stay Connected & Socials / ဆိုရှယ်မီဒီယာနှင့် မျှဝေရန် --}}
            <div class="space-y-3">
                @if (!empty($ftSocialLinks['facebook']) || !empty($ftSocialLinks['youtube']) || !empty($ftSocialLinks['tiktok']))
                    <div>
                        <h3 class="{{ $colHeader }}">
                            {{ __('messages.follow_us') }}
                        </h3>
                    </div>
                    <p class="text-xs sm:text-[13px] font-medium text-slate-800 dark:text-slate-200 font-myanmar">
                        သတင်းများနှင့် အထူးပရိုမိုးရှင်းများကို ဆိုရှယ်မီဒီယာများတွင် စောင့်ကြည့်နိုင်ပါသည်။
                    </p>
                @else
                    <div>
                        <h3 class="{{ $colHeader }}">
                            {{ __('messages.share') }}
                        </h3>
                    </div>
                @endif
                <div class="space-y-3 pt-1">

                    <div class="flex flex-wrap items-center gap-2">
                        @if (!empty($ftSocialLinks['facebook']))
                            <a href="{{ $ftSocialLinks['facebook'] }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-300 bg-white text-blue-600 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-900 dark:text-blue-400 shadow-2xs transition-colors"
                               title="Facebook" aria-label="Facebook">
                                <x-brand-icon brand="facebook" class="h-4 w-4 fill-current"/>
                            </a>
                        @endif
                        @if (!empty($ftSocialLinks['youtube']))
                            <a href="{{ $ftSocialLinks['youtube'] }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-300 bg-white text-red-600 hover:bg-red-50 dark:border-slate-700 dark:bg-slate-900 dark:text-red-400 shadow-2xs transition-colors"
                               title="YouTube" aria-label="YouTube">
                                <x-brand-icon brand="youtube" class="h-4 w-4 fill-current"/>
                            </a>
                        @endif
                        @if (!empty($ftSocialLinks['tiktok']))
                            <a href="{{ $ftSocialLinks['tiktok'] }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-300 bg-white text-slate-900 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-white shadow-2xs transition-colors"
                               title="TikTok" aria-label="TikTok">
                                <x-brand-icon brand="tiktok" class="h-4 w-4 fill-current"/>
                            </a>
                        @endif

                        <x-share-button
                            :url="$homeUrl"
                            :title="$storeDisplayName"
                            :label="__('messages.share')"
                            button-class="inline-flex h-9 items-center gap-1.5 rounded-md border border-slate-300 bg-white px-3 text-xs font-bold text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 shadow-2xs"
                            :show-viber="(bool) $ftViberUrl"
                            :show-telegram="(bool) $ftTelegramUrl"
                            :show-facebook="!empty($ftSocialLinks['facebook'])"
                        />
                    </div>

                    <div class="pt-2 border-t border-slate-300 dark:border-slate-800">
                        <a href="{{ $homeUrl }}" class="inline-flex items-center gap-2">
                            @if (!empty(($setting ?? null)?->storefrontLogo()))
                                <img src="{{ asset('storage/' . $setting->storefrontLogo()) }}" alt="{{ $storeDisplayName }}"
                                     class="h-9 w-auto max-w-[14rem] object-contain" loading="lazy" decoding="async" />
                            @else
                                <span class="font-outfit text-base font-black text-slate-950 dark:text-white">{{ $storeDisplayName }}</span>
                            @endif
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ================= 3. PAYMENT & DELIVERY PARTNERS STRIP (HIGH CONTRAST) ================= --}}
    <div class="border-t border-slate-300 bg-slate-200/90 dark:border-slate-800 dark:bg-slate-900/90 py-5">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-4">
            
            {{-- Pay With Bar --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                <span class="text-xs sm:text-[13px] font-extrabold text-slate-900 dark:text-white shrink-0 font-myanmar uppercase tracking-wide">
                    💳 {{ __('messages.payment') }}:
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($ftPaymentMethods->isNotEmpty())
                        @foreach ($ftPaymentMethods as $pm)
                            <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-extrabold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">
                                <x-payment-method-icon :method="$pm" class="h-3.5 w-3.5" text-class="text-[7px]" />
                                <span>{{ $pm->name }}</span>
                            </span>
                        @endforeach
                    @else
                        <span class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">KBZPay</span>
                        <span class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">WavePay</span>
                        <span class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">AYA / CB</span>
                        <span class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">Cash on Delivery</span>
                    @endif

                    @if ($setting?->payment_info)
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 font-myanmar ml-1">
                            ({{ $setting->payment_info }})
                        </span>
                    @endif
                </div>
            </div>

            {{-- Delivery Partners Bar --}}
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 pt-2.5 border-t border-slate-300 dark:border-slate-800">
                <span class="text-xs sm:text-[13px] font-extrabold text-slate-900 dark:text-white shrink-0 font-myanmar uppercase tracking-wide">
                    🚚 {{ __('messages.delivery') }}:
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($ftDeliveryMethods->isNotEmpty())
                        @foreach ($ftDeliveryMethods as $dm)
                            <span class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-extrabold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">
                                <span>{{ $dm->icon ?: '🚚' }}</span>
                                <span>{{ $dm->name }}</span>
                                @if ($dm->estimated_time)
                                    <span class="text-[11px] text-slate-600 dark:text-slate-400 font-semibold">({{ $dm->estimated_time }})</span>
                                @endif
                            </span>
                        @endforeach
                    @else
                        <span class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">🛵 အိမ်အရောက် Express</span>
                        <span class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 shadow-2xs">📦 ကားဂိတ် အမြန်ချော</span>
                    @endif

                    @if ($setting?->delivery_info)
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 font-myanmar ml-1">
                            ({{ $setting->delivery_info }})
                        </span>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- ================= 4. BOTTOM COPYRIGHT & BACK-TO-TOP ================= --}}
    <div class="border-t border-slate-300 bg-slate-900 text-slate-100 dark:border-slate-800 dark:bg-black py-4">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-myanmar">
            
            {{-- Copyright and footer ad --}}
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 text-center sm:text-left">
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
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-md bg-slate-800 text-white border border-slate-600 hover:bg-orange-600 hover:border-orange-500 transition-colors text-xs font-black cursor-pointer shadow-xs"
                title="{{ __('messages.back_to_top') }}"
            >
                <span>↑ {{ __('messages.back_to_top') }}</span>
            </button>
        </div>
    </div>

</footer>
