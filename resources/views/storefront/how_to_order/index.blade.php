@extends('layouts.storefront.app')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $query = $storeSlug ? '?store_slug=' . $storeSlug : '';
    $productsUrl = url('/products' . $query);
    $glassFinderUrl = url('/glass-finder' . $query);
    $orderBuilderUrl = url('/order-builder' . $query);

    $defaultIntro = 'ဒီဝက်ဆိုက်ကို ပထမဆုံးသုံးသူများအတွက်ပါ လွယ်အောင် ရေးထားပါတယ်။ ပစ္စည်းရှာ၊ စျေးကြည့်၊ Order List ထဲထည့်၊ ဆိုင်ကိုပို့၊ အတည်ပြုပြီးငွေပေးချေတဲ့အထိ အောက်ကအဆင့်တွေကို တစ်ဆင့်ချင်းလိုက်လုပ်ပါ။';
    $savedSteps = $setting?->how_to_steps ?? [];
    $steps = count($savedSteps) > 0
        ? collect($savedSteps)->map(fn ($step) => [
            'icon' => $step['icon'] ?? '📌',
            'title' => $step['title'] ?? '',
            'desc' => $step['desc'] ?? '',
        ])->values()->all()
        : [
            [
                'icon' => '🔎',
                'title' => 'ပစ္စည်းရှာပါ',
                'desc' => 'Products ထဲဝင်ပြီး Mobile, Accessories, CCTV, Computer, Fashion category တွေကနေ ရှာပါ။ ဖုန်းမှန်ရှာချင်ရင် Glass Finder ကိုနှိပ်ပြီး ဖုန်းမော်ဒယ်အမည်ရိုက်ပါ။',
            ],
            [
                'icon' => '👀',
                'title' => 'စျေးနှုန်းနဲ့ အချက်အလက်စစ်ပါ',
                'desc' => 'ပစ္စည်းပုံ၊ စျေးနှုန်း၊ stock ရှိ/မရှိ၊ variant အရောင်/size/storage တို့ကိုကြည့်ပါ။ Wholesale account နဲ့ဝင်ထားရင် wholesale စျေးကိုကြည့်နိုင်ပါတယ်။',
            ],
            [
                'icon' => '🛒',
                'title' => 'Order List ထဲထည့်ပါ',
                'desc' => 'လိုချင်တဲ့ပစ္စည်းကို Add to Order List နှိပ်ပါ။ တစ်ခုထက်ပိုယူချင်ရင် Qty ကိုပြင်ပါ။ ပြီးရင် Cart သို့မဟုတ် Order Builder ထဲမှာ စာရင်းစစ်ပါ။',
            ],
            [
                'icon' => '💬',
                'title' => 'ဆိုင်ကိုပို့ပြီး အတည်ပြုပါ',
                'desc' => 'နာမည်၊ ဖုန်းနံပါတ်၊ လိပ်စာ ထည့်ပြီး Viber / Telegram မှတစ်ဆင့်ပို့ပါ။ ဆိုင်ဘက်က stock, စျေးနှုန်း, delivery ခကို ပြန်အတည်ပြုပေးပါမယ်။',
            ],
            [
                'icon' => '📦',
                'title' => 'ငွေပေးချေပြီး ပစ္စည်းလက်ခံပါ',
                'desc' => 'KBZ Pay, Wave Pay, bank transfer သို့မဟုတ် Cash on Delivery အဆင်ပြေသလိုငွေပေးချေနိုင်ပါတယ်။ ပစ္စည်းကို ဆိုင်လာယူနိုင်သလို ပို့ဆောင်ရေးနဲ့လည်းပို့ပေးပါတယ်။',
            ],
        ];

    $stepTints = ['bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300', 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300', 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300', 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'];

    $quickTips = [
        ['label' => 'ဖုန်းမှန်ရှာမယ်', 'desc' => 'Glass Finder မှာ phone model ရိုက်ပါ', 'url' => $glassFinderUrl],
        ['label' => 'ပစ္စည်းရှာမယ်', 'desc' => 'Products ထဲက category/filter သုံးပါ', 'url' => $productsUrl],
        ['label' => 'Order စာရင်းစစ်မယ်', 'desc' => 'Order Builder ထဲမှာ qty/price စစ်ပါ', 'url' => $orderBuilderUrl],
    ];

    $viberUrl = \App\Support\ContactLinkBuilder::viberChatUrl($setting?->viber_number);
    $viberIosUrl = \App\Support\ContactLinkBuilder::viberIosContactUrl($setting?->viber_number);
    $telegramUrl = \App\Support\ContactLinkBuilder::telegramUrl($setting?->telegram_username);
    $callNumber = $setting?->phone ? \App\Support\ContactLinkBuilder::normalizeMyanmarPhone($setting->phone) : null;

    $noteRows = [
        ['icon' => '✓', 'tint' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300', 'text' => 'မသေချာရင် screenshot ပို့ပြီး မေးနိုင်ပါတယ်။'],
        ['icon' => '₭', 'tint' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300', 'text' => 'Website ထဲကစျေးနှုန်းကို ဆိုင်ဘက်က stock နဲ့ပြန်အတည်ပြုပေးပါမယ်။'],
        ['icon' => '🚚', 'tint' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300', 'text' => 'မြို့နယ်အလိုက် delivery ခ ကွာနိုင်လို့ order ပို့ပြီးနောက် ပြောပြပေးပါမယ်။'],
    ];
@endphp

<div class="mx-auto max-w-6xl space-y-10 sm:space-y-14">
    {{-- ===================== Hero — clean, borderless ===================== --}}
    <header class="space-y-6">
        <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-black text-sky-700 dark:border-sky-800 dark:bg-sky-950/50 dark:text-sky-300">
            <span>📖</span>
            <span>How to Order</span>
        </div>

        <div class="space-y-3">
            <h1 class="font-outfit text-3xl font-black leading-tight tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                {{ __('messages.how_to_hero_title', ['count' => count($steps)]) }}
            </h1>
            <p class="max-w-2xl text-sm leading-7 text-slate-600 dark:text-slate-300 font-myanmar">
                @php
                    // The intro must never contradict the rendered step cards:
                    // drop any embedded numbered-step lines ("1️⃣ …", "2. …")
                    // so a stored intro listing 6 steps can't disagree with 5
                    // active cards. Keep the lead paragraph + closing note.
                    $rawIntro = $setting?->how_to_intro ?: $defaultIntro;
                    $introLead = trim((string) preg_replace(
                        '/\n\s*\d+[️⃣\.\)\s]\s*[^\n]*/',
                        '',
                        $rawIntro
                    ));
                @endphp
                {!! nl2br(e($introLead)) !!}
            </p>
        </div>

        {{-- Quick links --}}
        <div class="grid gap-2.5 sm:grid-cols-3">
            @foreach ($quickTips as $tip)
                <a href="{{ $tip['url'] }}" class="group rounded-2xl border border-slate-200/70 bg-white p-4 transition hover:border-sky-300 hover:bg-sky-50/40 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-sky-700 dark:hover:bg-slate-800/60">
                    <span class="block text-sm font-black text-slate-900 transition group-hover:text-sky-700 dark:text-white dark:group-hover:text-sky-300">{{ $tip['label'] }}</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $tip['desc'] }}</span>
                </a>
            @endforeach
        </div>

        {{-- CTA buttons --}}
        <div class="flex flex-wrap gap-2.5">
            <a href="{{ $orderBuilderUrl }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-violet-500 active:scale-95">
                <span>🛒</span>
                <span>Order Builder ဖွင့်မယ်</span>
            </a>
            @if ($viberUrl)
                <a href="{{ $viberUrl }}" data-ios-href="{{ $viberIosUrl }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-purple-600 px-5 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-purple-500 active:scale-95">
                    <x-brand-icon brand="viber" class="h-4 w-4"/>
                    <span>Viber မေးမယ်</span>
                </a>
            @endif
        </div>
    </header>

    {{-- ===================== Notes strip ===================== --}}
    <section class="border-y border-slate-100 py-6 dark:border-slate-800">
        <h2 class="text-sm font-black text-slate-900 dark:text-white">မှာယူရာမှာ မှတ်ထားရန်</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            @foreach ($noteRows as $note)
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-black {{ $note['tint'] }}">{{ $note['icon'] }}</span>
                    <p class="pt-1 text-xs leading-6 text-slate-600 dark:text-slate-300 font-myanmar">{{ $note['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===================== Steps ===================== --}}
    <section class="space-y-6">
        <div class="space-y-1.5">
            <p class="text-xs font-black uppercase tracking-widest text-violet-600 dark:text-violet-300">Step by Step</p>
            <h2 class="font-outfit text-xl font-black tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                မှာယူနည်း အဆင့်ဆင့်
            </h2>
        </div>

        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($steps as $i => $step)
                <article class="rounded-2xl border border-slate-200/70 bg-white p-4 transition hover:border-sky-300 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-sky-700">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg {{ $stepTints[$i % count($stepTints)] }}">{{ $step['icon'] }}</span>
                        <span class="text-[11px] font-black tracking-wider text-slate-300 dark:text-slate-600">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <h3 class="mt-3 text-sm font-black leading-6 text-slate-950 dark:text-white">
                        {{ $step['title'] }}
                    </h3>
                    <p class="mt-1.5 text-xs leading-6 text-slate-500 dark:text-slate-400 font-myanmar">
                        {{ $step['desc'] }}
                    </p>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ===================== Contact / Payment / Delivery ===================== --}}
    <section class="grid gap-10 lg:grid-cols-3 lg:gap-8">
        <div class="space-y-4">
            <h2 class="text-sm font-black text-slate-900 dark:text-white">ဆိုင်နဲ့တိုက်ရိုက်ဆက်သွယ်ရန်</h2>
            <p class="text-xs leading-6 text-slate-500 dark:text-slate-400 font-myanmar">{{ __('messages.how_to_order_contact_hint') }}</p>
            <div class="flex flex-wrap gap-2">
                @if ($callNumber)
                    <a href="tel:{{ $callNumber }}" class="inline-flex min-h-10 items-center rounded-xl bg-emerald-600 px-4 py-2 text-xs font-black text-white transition hover:bg-emerald-500 active:scale-95">📞 ဖုန်းခေါ်မယ်</a>
                @endif
                @if ($viberUrl)
                    <a href="{{ $viberUrl }}" data-ios-href="{{ $viberIosUrl }}" class="inline-flex min-h-10 items-center rounded-xl bg-purple-600 px-4 py-2 text-xs font-black text-white transition hover:bg-purple-500 active:scale-95">Viber</a>
                @endif
                @if ($telegramUrl)
                    <a href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center rounded-xl bg-sky-600 px-4 py-2 text-xs font-black text-white transition hover:bg-sky-500 active:scale-95">Telegram</a>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <h2 class="text-sm font-black text-slate-900 dark:text-white">ငွေပေးချေနည်း</h2>
            @php $activePayments = $store?->paymentMethods()->active()->get(); @endphp
            @if ($activePayments && $activePayments->isNotEmpty())
                <div class="space-y-2">
                    @foreach ($activePayments as $pm)
                        <div class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-800/40">
                            <x-payment-method-icon :method="$pm" class="h-8 w-8 shrink-0" />
                            <div class="min-w-0">
                                <p class="text-xs font-black text-slate-900 dark:text-white">{{ $pm->name }}</p>
                                @if ($pm->show_account_details && ($pm->account_number || $pm->account_name))
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ $pm->account_name ? $pm->account_name . ' · ' : '' }}{{ $pm->account_number }}
                                    </p>
                                @endif
                                @if ($pm->instructions)
                                    <p class="mt-0.5 text-[11px] leading-5 text-slate-500 dark:text-slate-500">{{ $pm->instructions }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif ($setting?->payment_info)
                <p class="whitespace-pre-line text-xs leading-6 text-slate-500 dark:text-slate-400 font-myanmar">{{ $setting->payment_info }}</p>
            @else
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full border border-sky-200 bg-sky-50 px-3.5 py-1.5 text-xs font-black text-sky-800 dark:border-sky-800 dark:bg-sky-950/50 dark:text-sky-300">KBZ Pay</span>
                    <span class="rounded-full border border-amber-200 bg-amber-50 px-3.5 py-1.5 text-xs font-black text-amber-800 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300">Wave Pay</span>
                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3.5 py-1.5 text-xs font-black text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">Cash</span>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <h2 class="text-sm font-black text-slate-900 dark:text-white">ဆိုင်လိပ်စာ / ပို့ဆောင်ရေး</h2>
            <div class="space-y-2.5 text-xs leading-6 text-slate-500 dark:text-slate-400 font-myanmar">
                <p class="flex items-start gap-2.5"><span class="text-base leading-6">📍</span><span>{{ $setting?->address ?? 'DataPOS' }}</span></p>
                <p class="flex items-start gap-2.5"><span class="text-base leading-6">🕒</span><span>{{ $setting?->opening_hours ?: '9:00AM to 5:30PM' }}</span></p>
                @php $activeDeliveries = $store?->deliveryMethods()->active()->get(); @endphp
                @if ($activeDeliveries && $activeDeliveries->isNotEmpty())
                    <div class="space-y-2">
                        @foreach ($activeDeliveries as $dm)
                            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-800/40">
                                <p class="text-xs font-black text-slate-900 dark:text-white">{{ $dm->icon ?: '🚚' }} {{ $dm->name }}
                                    @if ($dm->estimated_time) <span class="font-bold text-sky-700 dark:text-sky-300">· {{ $dm->estimated_time }}</span> @endif
                                </p>
                                @if ($dm->fee_note || $dm->service_area)
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-500">{{ collect([$dm->service_area, $dm->fee_note])->filter()->implode(' · ') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif ($setting?->delivery_info)
                    <p class="whitespace-pre-line">{{ $setting->delivery_info }}</p>
                @else
                    <p>မြန်မာနိုင်ငံအနှံ့ ပို့ဆောင်ပေးနိုင်ပါတယ်။ Delivery ခကို order အတည်ပြုချိန်မှာ ပြန်ပြောပေးပါမယ်။</p>
                @endif
            </div>
        </div>
    </section>

    {{-- ===================== Visit Our Store — exact map card ===================== --}}
    @if ($setting?->map_enabled && ($setting->mapUrl() || $setting?->address))
    <section class="space-y-6">
        <div class="space-y-1.5">
            <h2 class="font-outfit text-xl font-black tracking-tight text-slate-950 dark:text-white">{{ $setting->map_title ?: __('messages.visit_our_store') }}</h2>
            <div class="flex flex-wrap gap-x-6 gap-y-1.5 text-xs leading-6 text-slate-500 dark:text-slate-400 font-myanmar">
                <span class="inline-flex items-center gap-1.5">📍 {{ $setting?->address }}</span>
                <span class="inline-flex items-center gap-1.5">🕒 {{ $setting?->opening_hours ?: '9:00AM to 5:30PM' }}</span>
                @if ($setting?->phone)
                    <span class="inline-flex items-center gap-1.5">📞 {{ $setting->phone }}</span>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex flex-wrap gap-2">
                @if ($setting->mapUrl())
                    <a href="{{ $setting->mapUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center gap-1.5 rounded-xl bg-slate-800 px-4 py-2 text-xs font-black text-white transition hover:bg-slate-700 active:scale-95">
                        <svg class="h-3.5 w-3.5 text-red-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        {{ __('messages.open_in_google_maps') }}
                    </a>
                @endif
                @if ($setting->mapDirectionsUrl())
                    <a href="{{ $setting->mapDirectionsUrl() }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center rounded-xl bg-sky-600 px-4 py-2 text-xs font-black text-white transition hover:bg-sky-500 active:scale-95">
                        🧭 {{ __('messages.get_directions') }}
                    </a>
                @endif
            </div>
            @if ($setting->map_embed_enabled && $setting->mapEmbedSrc())
                <div class="w-full lg:w-96 shrink-0 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                    <div class="relative aspect-video w-full bg-slate-100 dark:bg-slate-800">
                        <iframe class="absolute inset-0 h-full w-full border-0" src="{{ $setting->mapEmbedSrc() }}" title="{{ $setting->map_title ?: __('messages.visit_our_store') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    </div>
                </div>
            @endif
        </div>
    </section>
    @endif

    {{-- ===================== Video tutorials ===================== --}}
    @php
        $videos = $setting?->how_to_videos ?? [];
        $channelLinks = array_values(array_filter([
            ['url' => $setting?->youtube_url, 'label' => __('messages.watch_on_youtube'), 'class' => 'bg-red-600 hover:bg-red-500'],
            ['url' => $setting?->tiktok_url, 'label' => __('messages.watch_on_tiktok'), 'class' => 'bg-slate-900 hover:bg-slate-700'],
            ['url' => $setting?->facebook_url, 'label' => __('messages.watch_on_facebook'), 'class' => 'bg-blue-600 hover:bg-blue-500'],
        ], fn ($c) => !empty($c['url'])));
    @endphp

    @if (count($videos) > 0 || count($channelLinks) > 0)
        <section class="space-y-6">
            <div class="space-y-1.5 text-center">
                <h2 class="font-outfit text-xl font-black tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                    {{ __('messages.video_tutorials') }}
                </h2>
                <p class="mx-auto max-w-xl text-xs leading-6 text-slate-500 dark:text-slate-400 font-myanmar">
                    {{ __('messages.video_tutorials_hint') }}
                </p>
            </div>

            @if (count($videos) > 0)
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($videos as $video)
                        @php
                            $videoUrl = $video['url'] ?? '';
                            $videoTitle = $video['title'] ?? null;
                            $ytId = preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/', $videoUrl, $m) ? $m[1] : null;
                            $isTikTok = str_contains($videoUrl, 'tiktok.com');
                        @endphp
                        @if ($ytId)
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                                <div class="relative aspect-video w-full bg-black">
                                    <iframe class="absolute inset-0 h-full w-full" src="https://www.youtube.com/embed/{{ $ytId }}" title="{{ $videoTitle ?? __('messages.video_tutorials') }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                                </div>
                                @if ($videoTitle)
                                    <p class="px-4 py-3 text-xs font-extrabold text-slate-800 dark:text-slate-200">{{ $videoTitle }}</p>
                                @endif
                            </div>
                        @else
                            <a href="{{ $videoUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 transition hover:border-sky-300 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-sky-700">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $isTikTok ? 'bg-slate-900' : 'bg-rose-600' }} text-xl text-white">🎬</span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-black text-slate-900 dark:text-white">{{ $videoTitle ?: ($isTikTok ? __('messages.watch_on_tiktok') : 'Watch video') }}</span>
                                    <span class="block text-xs font-bold {{ $isTikTok ? 'text-slate-500' : 'text-rose-600' }}">{{ $isTikTok ? __('messages.watch_on_tiktok') : __('messages.watch_on_youtube') }}</span>
                                </span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            @if (count($channelLinks) > 0)
                <div class="flex flex-wrap items-center justify-center gap-2">
                    @foreach ($channelLinks as $ch)
                        <a href="{{ $ch['url'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 {{ $ch['class'] }} text-xs font-black text-white shadow-sm transition hover:scale-[1.02] active:scale-95">
                            {{ $ch['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>
@endsection
