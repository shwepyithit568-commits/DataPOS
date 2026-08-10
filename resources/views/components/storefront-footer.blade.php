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
    $glassFinderUrl = $storeSlug ? url('/glass-finder?store_slug=' . $storeSlug) : url('/glass-finder');
    $howToOrderUrl = $storeSlug ? url('/how-to-order?store_slug=' . $storeSlug) : url('/how-to-order');
    $blogUrl = $storeSlug ? url('/blog?store_slug=' . $storeSlug) : url('/blog');
    $accountUrl = $storeSlug ? url('/account?store_slug=' . $storeSlug) : url('/account');

    $ftViberUrl = \App\Support\ContactLinkBuilder::viberChatUrl($setting?->viber_number);
    $ftViberIosUrl = \App\Support\ContactLinkBuilder::viberIosContactUrl($setting?->viber_number);
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

    // Shared styles — one clean set for every breakpoint (no mobile/desktop
    // duplication): simple link lists, subtle payment chips, circle socials.
    $ftHeading = 'text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400';
    $ftLink = 'text-sm text-slate-600 transition hover:text-sky-600 dark:text-slate-300 dark:hover:text-sky-400';
    $ftChip = 'inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300';
    $ftSocialBtn = 'inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-sky-600 dark:hover:bg-slate-700 dark:hover:text-sky-400';
@endphp

<footer class="w-full border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-12 lg:gap-8">

            {{-- Brand + tagline + socials (desktop: 4 cols) --}}
            <div class="flex flex-col gap-4 lg:col-span-4">
                <a href="{{ $homeUrl }}" class="inline-flex items-center gap-2.5">
                    @if (!empty(($setting ?? null)?->storefrontLogo()))
                        <img src="{{ asset('storage/' . $setting->storefrontLogo()) }}" alt="{{ $storeDisplayName }}"
                             class="h-9 w-auto max-w-[12rem] object-contain" loading="lazy" decoding="async" />
                    @else
                        <span class="font-outfit text-lg font-black tracking-tight text-slate-900 dark:text-white">{{ $storeDisplayName }}</span>
                    @endif
                </a>
                @if ($setting?->tagline)
                    <p class="max-w-sm text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $setting->tagline }}</p>
                @endif

                {{-- Social icons — circle buttons on every breakpoint --}}
                <div class="mt-1 flex flex-wrap items-center gap-2.5" @if ($ftSocialLinks) aria-label="{{ __('messages.follow_us') }}" @endif>
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
                            button-class="inline-flex h-9 items-center gap-1.5 rounded-full border border-slate-200 bg-slate-50 px-3.5 text-xs font-bold text-slate-600 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-sky-600 dark:hover:bg-slate-700 dark:hover:text-sky-400"
                            :show-viber="(bool) $ftViberUrl"
                            :show-telegram="(bool) $ftTelegramUrl"
                            :show-facebook="!empty($ftSocialLinks['facebook'])"
                    />
                </div>
            </div>

            {{-- Customer Service (desktop: 2 cols) --}}
            <div class="lg:col-span-2">
                <h4 class="{{ $ftHeading }} mb-4">{{ __('messages.customer_service') }}</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ $howToOrderUrl }}" class="{{ $ftLink }}">{{ __('messages.how_to_order') }}</a></li>
                    <li><a href="{{ $glassFinderUrl }}" class="{{ $ftLink }}">{{ __('messages.glass_finder') }}</a></li>
                    <li><a href="{{ $blogUrl }}" class="{{ $ftLink }}">{{ __('messages.blog') }}</a></li>
                    <li><a href="{{ $accountUrl }}" class="{{ $ftLink }}">{{ __('messages.account') }}</a></li>
                </ul>
            </div>

            {{-- Contact (desktop: 3 cols) --}}
            <div class="lg:col-span-3">
                <h4 class="{{ $ftHeading }} mb-4">{{ __('messages.contact') }}</h4>
                <ul class="space-y-2.5">
                    @if ($setting?->address)
                        <li class="flex items-start gap-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                            <span aria-hidden="true">📍</span>
                            <span>{{ $setting->address }}</span>
                        </li>
                    @endif
                    @if ($setting?->phone)
                        <li>
                            <a href="tel:{{ $setting->phone }}" class="{{ $ftLink }} inline-flex items-center gap-2">📞 {{ $setting->phone }}</a>
                        </li>
                    @endif
                    @if ($ftViberUrl)
                        <li>
                            <a href="{{ $ftViberUrl }}" data-ios-href="{{ $ftViberIosUrl }}" class="{{ $ftLink }} inline-flex items-center gap-2">
                                <x-brand-icon brand="viber" class="h-4 w-4 text-violet-600 dark:text-violet-400"/> Viber
                            </a>
                        </li>
                    @endif
                    @if ($ftTelegramUrl)
                        <li>
                            <a href="{{ $ftTelegramUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $ftLink }} inline-flex items-center gap-2">
                                <x-brand-icon brand="telegram" class="h-4 w-4 text-sky-600 dark:text-sky-400"/> Telegram
                            </a>
                        </li>
                    @endif
                    @if ($ftMapUrl || $ftMapDirectionsUrl)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-2 pt-1">
                            @if ($ftMapUrl)
                                <a href="{{ $ftMapUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $ftLink }} inline-flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-red-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                    {{ __('messages.view_on_map') }}
                                </a>
                            @endif
                            @if ($ftMapDirectionsUrl)
                                <a href="{{ $ftMapDirectionsUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $ftLink }} inline-flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-sky-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                    {{ __('messages.get_directions') }}
                                </a>
                            @endif
                        </li>
                    @endif
                </ul>
                @if ($ftViberUrl)
                    <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">
                        {{ __('messages.viber_missing') }}
                        <a href="https://www.viber.com/download/" target="_blank" rel="noopener noreferrer"
                           class="font-bold text-sky-600 transition hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">{{ __('messages.viber_install') }} →</a>
                    </p>
                @endif
            </div>

            {{-- Payment + Delivery (desktop: 3 cols) --}}
            <div class="flex flex-col gap-6 lg:col-span-3">
                <div>
                    <h4 class="{{ $ftHeading }} mb-4">{{ __('messages.payment') }}</h4>
                    @if ($ftPaymentMethods->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($ftPaymentMethods as $ftPm)
                                <span class="{{ $ftChip }}">
                                    <x-payment-method-icon :method="$ftPm" class="h-4 w-4" text-class="text-[7px]" />
                                    {{ $ftPm->name }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('messages.footer_payment_note') }}</p>
                        <a href="{{ $howToOrderUrl }}" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-sky-600 transition hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">
                            {{ __('messages.see_details') }} →
                        </a>
                    @endif
                </div>

                @if ($ftDeliveryMethods->isNotEmpty() || $setting?->delivery_info)
                <div>
                    <h4 class="{{ $ftHeading }} mb-4">{{ __('messages.delivery') }}</h4>
                    @if ($ftDeliveryMethods->isNotEmpty())
                        <div class="flex flex-wrap gap-2">
                            @foreach ($ftDeliveryMethods as $ftDm)
                                <span class="{{ $ftChip }}">{{ $ftDm->name }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('messages.footer_delivery_note') }}</p>
                        <a href="{{ $howToOrderUrl }}" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-sky-600 transition hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">
                            {{ __('messages.see_details') }} →
                        </a>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Bottom bar --}}
        <div class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-slate-100 dark:border-slate-800 pt-6 sm:flex-row">
            <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $storeDisplayName }}</span>
            <p class="max-w-xl text-center text-sm text-slate-500 dark:text-slate-400 sm:text-right">
                @if (!empty($setting?->footer_ad_text))
                    {{ $setting->footer_ad_text }}
                @else
                    © {{ date('Y') }} DataPOS
                @endif
            </p>
        </div>
    </div>
</footer>
