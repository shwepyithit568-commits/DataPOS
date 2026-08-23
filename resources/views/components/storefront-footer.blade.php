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

    $homeUrl = $storeSlug ? url('/?store_slug=' . $storeSlug) : url('/');
    $productsUrl = $storeSlug ? url('/products?store_slug=' . $storeSlug) : url('/products');
    $glassFinderUrl = $storeSlug ? url('/glass-finder?store_slug=' . $storeSlug) : url('/glass-finder');
    $serviceTrackingUrl = $storeSlug ? url('/service-tracking?store_slug=' . $storeSlug) : url('/service-tracking');
    $howToOrderUrl = $storeSlug ? url('/how-to-order?store_slug=' . $storeSlug) : url('/how-to-order');
    $blogUrl = $storeSlug ? url('/blog?store_slug=' . $storeSlug) : url('/blog');
    $accountUrl = $storeSlug ? url('/account?store_slug=' . $storeSlug) : url('/account');

    $ftViberUrl = \App\Support\ContactLinkBuilder::viberChatUrl($setting?->viber_number ?? $setting?->phone);
    $ftViberIosUrl = \App\Support\ContactLinkBuilder::viberIosContactUrl($setting?->viber_number ?? $setting?->phone);
    $ftTelegramUrl = \App\Support\ContactLinkBuilder::telegramUrl($setting?->telegram_username);
    $ftSocialLinks = [
        'facebook' => trim((string) ($setting?->facebook_url ?? '')),
        'youtube'  => trim((string) ($setting?->youtube_url ?? '')),
        'tiktok'   => trim((string) ($setting?->tiktok_url ?? '')),
    ];
    $ftSocialLinks = array_filter($ftSocialLinks, fn($url) => preg_match('#^https?://#i', $url));

    $ftPaymentMethods = $store?->paymentMethods()->active()->get() ?? collect();
    $ftDeliveryMethods = $store?->deliveryMethods()->active()->get() ?? collect();
    $ftMapUrl = $setting?->mapUrl();
    $ftMapDirectionsUrl = $setting?->mapDirectionsUrl();

    // Standardized typography and styles
    $ftHeading = 'text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white font-outfit flex items-center gap-2';
    $ftLink = 'text-xs sm:text-sm text-slate-600 transition-colors duration-150 hover:text-sky-600 dark:text-slate-400 dark:hover:text-sky-300 font-myanmar';
    $ftSocialBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition-all hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 active:scale-95 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-sky-600 dark:hover:bg-slate-800 dark:hover:text-sky-400 shadow-2xs';
@endphp

<footer class="w-full border-t border-slate-200/80 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-950 transition-colors">

    {{-- Trust & Value Proposition Strip (Top-tier regional e-commerce standard) --}}
    <div class="border-b border-slate-200/70 dark:border-slate-800/70 bg-white dark:bg-slate-900/60">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                
                {{-- Value 1: Express Delivery --}}
                <div class="flex items-center gap-3.5 p-2">
                    <div class="w-11 h-11 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center text-xl shrink-0 shadow-2xs border border-sky-100 dark:border-sky-900/50">
                        ⚡
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar leading-snug">အမြန်ပို့ဆောင်မှု</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar truncate">အိမ်အရောက် & ကားဂိတ် အမြန်ပို့</p>
                    </div>
                </div>

                {{-- Value 2: Warranty & Quality --}}
                <div class="flex items-center gap-3.5 p-2">
                    <div class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl shrink-0 shadow-2xs border border-emerald-100 dark:border-emerald-900/50">
                        🛡️
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar leading-snug">စိတ်ချရသော အာမခံ</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar truncate">စစ်မှန်သော ပစ္စည်းနှင့် အာမခံ</p>
                    </div>
                </div>

                {{-- Value 3: Live Service Tracking --}}
                <div class="flex items-center gap-3.5 p-2">
                    <div class="w-11 h-11 rounded-2xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center text-xl shrink-0 shadow-2xs border border-teal-100 dark:border-teal-900/50">
                        🔧
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar leading-snug">Service Tracking</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar truncate">စက်ပြင် အခြေအနေ စစ်ဆေးနိုင်</p>
                    </div>
                </div>

                {{-- Value 4: Direct Chat & Support --}}
                <div class="flex items-center gap-3.5 p-2">
                    <div class="w-11 h-11 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xl shrink-0 shadow-2xs border border-purple-100 dark:border-purple-900/50">
                        💬
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-black text-xs sm:text-sm text-slate-900 dark:text-white font-myanmar leading-snug">တိုက်ရိုက် အကူအညီ</h4>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar truncate">Viber / Telegram ဖြင့် အချိန်မရွေး</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Main Multi-Column Links Section --}}
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-12 lg:gap-8">

            {{-- Column 1: Store Identity & Socials (Desktop: 4 cols) --}}
            <div class="flex flex-col gap-4 sm:col-span-2 lg:col-span-4">
                <a href="{{ $homeUrl }}" class="inline-flex items-center gap-2.5">
                    @if (!empty(($setting ?? null)?->storefrontLogo()))
                        <img src="{{ asset('storage/' . $setting->storefrontLogo()) }}" alt="{{ $storeDisplayName }}"
                             class="h-9 sm:h-10 w-auto max-w-[14rem] object-contain" loading="lazy" decoding="async" />
                    @else
                        <span class="font-outfit text-xl font-black tracking-tight text-slate-900 dark:text-white">{{ $storeDisplayName }}</span>
                    @endif
                </a>

                @if ($setting?->tagline)
                    <p class="max-w-sm text-xs sm:text-sm leading-relaxed text-slate-600 dark:text-slate-400 font-myanmar">
                        {{ $setting->tagline }}
                    </p>
                @else
                    <p class="max-w-sm text-xs sm:text-sm leading-relaxed text-slate-500 dark:text-slate-400 font-myanmar">
                        {{ __('messages.store_about') }}
                    </p>
                @endif

                {{-- Verified Store Badge --}}
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-teal-50 dark:bg-teal-950/50 border border-teal-200/80 dark:border-teal-800 text-teal-800 dark:text-teal-300 text-xs font-bold font-myanmar self-start shadow-2xs">
                    <span>🛡️</span>
                    <span>တရားဝင် အသိအမှတ်ပြု အရောင်းဆိုင်</span>
                </div>

                {{-- Social & Share Buttons --}}
                <div class="flex flex-wrap items-center gap-2 pt-1" @if ($ftSocialLinks) aria-label="{{ __('messages.follow_us') }}" @endif>
                    @if (!empty($ftSocialLinks['facebook']))
                        <a href="{{ $ftSocialLinks['facebook'] }}" target="_blank" rel="noopener noreferrer" class="{{ $ftSocialBtn }}" aria-label="Facebook" title="Facebook">
                            <x-brand-icon brand="facebook" class="h-4 w-4 text-blue-600 dark:text-blue-400"/>
                        </a>
                    @endif
                    @if (!empty($ftSocialLinks['youtube']))
                        <a href="{{ $ftSocialLinks['youtube'] }}" target="_blank" rel="noopener noreferrer" class="{{ $ftSocialBtn }}" aria-label="YouTube" title="YouTube">
                            <x-brand-icon brand="youtube" class="h-4 w-4 text-red-600 dark:text-red-400"/>
                        </a>
                    @endif
                    @if (!empty($ftSocialLinks['tiktok']))
                        <a href="{{ $ftSocialLinks['tiktok'] }}" target="_blank" rel="noopener noreferrer" class="{{ $ftSocialBtn }}" aria-label="TikTok" title="TikTok">
                            <x-brand-icon brand="tiktok" class="h-4 w-4 text-slate-900 dark:text-slate-300"/>
                        </a>
                    @endif

                    <x-share-button
                        :url="$homeUrl"
                        :title="$storeDisplayName"
                        :label="__('messages.share')"
                        button-class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 text-xs font-bold text-slate-700 transition-all hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 active:scale-95 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-sky-600 dark:hover:bg-slate-800 dark:hover:text-sky-400 shadow-2xs"
                        :show-viber="(bool) $ftViberUrl"
                        :show-telegram="(bool) $ftTelegramUrl"
                        :show-facebook="!empty($ftSocialLinks['facebook'])"
                    />
                </div>
            </div>

            {{-- Column 2: Quick Links & Services (Desktop: 2 cols) --}}
            <div class="col-span-1 sm:col-span-1 lg:col-span-2 space-y-3.5">
                <h4 class="{{ $ftHeading }}">
                    <span>📖</span>
                    <span>{{ __('messages.customer_service') }}</span>
                </h4>
                <ul class="space-y-2">
                    <li>
                        <a href="{{ $homeUrl }}" class="{{ $ftLink }} block py-0.5 hover:translate-x-0.5 transition-transform">
                            {{ __('messages.home') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $productsUrl }}" class="{{ $ftLink }} block py-0.5 hover:translate-x-0.5 transition-transform">
                            {{ __('messages.products') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $glassFinderUrl }}" class="{{ $ftLink }} block py-0.5 hover:translate-x-0.5 transition-transform">
                            📱 {{ __('messages.glass_finder') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $serviceTrackingUrl }}" class="{{ $ftLink }} block py-0.5 font-bold text-teal-600 dark:text-teal-400 hover:translate-x-0.5 transition-transform">
                            🔧 {{ __('messages.nav_service_track') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $howToOrderUrl }}" class="{{ $ftLink }} block py-0.5 hover:translate-x-0.5 transition-transform">
                            {{ __('messages.how_to_order') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $blogUrl }}" class="{{ $ftLink }} block py-0.5 hover:translate-x-0.5 transition-transform">
                            {{ __('messages.blog') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ $accountUrl }}" class="{{ $ftLink }} block py-0.5 hover:translate-x-0.5 transition-transform">
                            {{ __('messages.account') }}
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Column 3: Contact & Address (Desktop: 3 cols) --}}
            <div class="col-span-1 sm:col-span-1 lg:col-span-3 space-y-3.5">
                <h4 class="{{ $ftHeading }}">
                    <span>📞</span>
                    <span>{{ __('messages.contact') }}</span>
                </h4>
                <ul class="space-y-2.5">
                    @if ($setting?->address ?? $store?->address)
                        <li class="flex items-start gap-2.5 text-xs sm:text-sm leading-relaxed text-slate-600 dark:text-slate-400 font-myanmar">
                            <span class="shrink-0 pt-0.5 text-slate-400" aria-hidden="true">📍</span>
                            <span>{{ $setting?->address ?? $store?->address }}</span>
                        </li>
                    @endif

                    @if ($setting?->phone ?? $store?->phone)
                        <li>
                            <a href="tel:{{ $setting?->phone ?? $store?->phone }}" class="{{ $ftLink }} inline-flex items-center gap-2 font-mono font-bold">
                                <span>📞</span>
                                <span>{{ $setting?->phone ?? $store?->phone }}</span>
                            </a>
                        </li>
                    @endif

                    @if ($ftViberUrl)
                        <li>
                            <a href="{{ $ftViberUrl }}" data-ios-href="{{ $ftViberIosUrl ?? $ftViberUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $ftLink }} inline-flex items-center gap-2">
                                <x-brand-icon brand="viber" class="h-4 w-4 text-violet-600 dark:text-violet-400 shrink-0"/>
                                <span>Viber Chat</span>
                            </a>
                        </li>
                    @endif

                    @if ($ftTelegramUrl)
                        <li>
                            <a href="{{ $ftTelegramUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $ftLink }} inline-flex items-center gap-2">
                                <x-brand-icon brand="telegram" class="h-4 w-4 text-sky-600 dark:text-sky-400 shrink-0"/>
                                <span>Telegram Chat</span>
                            </a>
                        </li>
                    @endif

                    @if ($setting?->opening_hours)
                        <li class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 font-myanmar pt-0.5">
                            <span aria-hidden="true">⏰</span>
                            <span>{{ __('messages.opening_hours') }}: {{ $setting->opening_hours }}</span>
                        </li>
                    @endif

                    @if ($ftMapUrl || $ftMapDirectionsUrl)
                        <li class="flex flex-wrap items-center gap-2 pt-1">
                            @if ($ftMapUrl)
                                <a href="{{ $ftMapUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold text-rose-600 dark:text-rose-400 shadow-2xs hover:border-rose-300 transition">
                                    <svg class="h-3.5 w-3.5 text-rose-500 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                    <span>{{ __('messages.view_on_map') }}</span>
                                </a>
                            @endif
                            @if ($ftMapDirectionsUrl)
                                <a href="{{ $ftMapDirectionsUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-bold text-sky-600 dark:text-sky-400 shadow-2xs hover:border-sky-300 transition">
                                    <svg class="h-3.5 w-3.5 text-sky-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                    <span>{{ __('messages.get_directions') }}</span>
                                </a>
                            @endif
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Column 4: Payment & Delivery (Desktop: 3 cols) --}}
            <div class="flex flex-col gap-6 col-span-1 sm:col-span-2 lg:col-span-3">
                
                {{-- Payment Methods Card --}}
                <div class="rounded-2xl bg-white dark:bg-slate-900 p-4 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-2.5">
                    <h4 class="{{ $ftHeading }}">
                        <span>💳</span>
                        <span>{{ __('messages.payment') }}</span>
                    </h4>

                    @if ($ftPaymentMethods->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($ftPaymentMethods as $ftPm)
                                <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200/90 bg-slate-50 dark:border-slate-800 dark:bg-slate-800 px-2.5 py-1 text-xs font-bold text-slate-700 dark:text-slate-300 shadow-2xs">
                                    <x-payment-method-icon :method="$ftPm" class="h-3.5 w-3.5" text-class="text-[7px]" />
                                    <span>{{ $ftPm->name }}</span>
                                </span>
                            @endforeach
                        </div>
                    @else
                        {{-- Myanmar Standard Payment Options --}}
                        <div class="flex flex-wrap gap-1.5">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 border border-violet-200/80 dark:border-violet-800 font-bold text-xs">
                                <span class="w-2 h-2 rounded-full bg-violet-600"></span> KBZPay
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-cyan-50 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 border border-cyan-200/80 dark:border-cyan-800 font-bold text-xs">
                                <span class="w-2 h-2 rounded-full bg-cyan-500"></span> WavePay
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200/80 dark:border-emerald-800 font-bold text-xs">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> CB / AYA
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-800 font-bold text-xs">
                                💵 Cash on Delivery
                            </span>
                        </div>
                    @endif

                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar pt-0.5">
                        {{ __('messages.footer_payment_note') }}
                    </p>
                </div>

                {{-- Delivery Options Card --}}
                <div class="rounded-2xl bg-white dark:bg-slate-900 p-4 sm:p-5 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-2.5">
                    <h4 class="{{ $ftHeading }}">
                        <span>🚚</span>
                        <span>{{ __('messages.delivery') }}</span>
                    </h4>

                    @if ($ftDeliveryMethods->isNotEmpty())
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($ftDeliveryMethods as $ftDm)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold">
                                    🚚 {{ $ftDm->name }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-wrap gap-1.5">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200/80 dark:border-sky-800 text-xs font-bold">
                                🛵 အိမ်အရောက် Express
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-xs font-bold">
                                📦 ကားဂိတ် အမြန်ချော
                            </span>
                        </div>
                    @endif

                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar pt-0.5">
                        {{ __('messages.footer_delivery_note') }}
                    </p>
                </div>

            </div>

        </div>

        {{-- Bottom Copyright Bar with Back-To-Top --}}
        <div class="mt-10 sm:mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-200/80 dark:border-slate-800 pt-6 sm:flex-row text-xs text-slate-500 dark:text-slate-400 font-myanmar">
            <div class="flex items-center gap-2">
                <span class="font-black text-slate-900 dark:text-white font-outfit text-sm">{{ $storeDisplayName }}</span>
                <span>·</span>
                <span>
                    @if (!empty($setting?->footer_ad_text))
                        {{ $setting->footer_ad_text }}
                    @else
                        © {{ date('Y') }} DataPOS. All rights reserved.
                    @endif
                </span>
            </div>

            {{-- Back to top button --}}
            <button
                type="button"
                @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold hover:text-sky-600 dark:hover:text-sky-400 hover:border-sky-300 transition-all shadow-2xs active:scale-95 cursor-pointer"
            >
                <span>↑ ထိပ်ဆုံးသို့ ပြန်သွားမည်</span>
            </button>
        </div>

    </div>
</footer>
