@extends('layouts.storefront.app')

@section('content')
@php
    $storeSlug = $store?->slug ?? request('store_slug');
    $query = $storeSlug ? '?store_slug=' . $storeSlug : '';
    $productsUrl = url('/products' . $query);
    $glassFinderUrl = url('/glass-finder' . $query);
    $orderBuilderUrl = url('/order-builder' . $query);

    $defaultIntro = 'နည်းပညာ မကျွမ်းကျင်သူများနှင့် ဝက်ဘ်ဆိုက်ကို ပထမဆုံး စတင်အသုံးပြုမည့် ဖောက်သည်များပါ အလွယ်တကူ စျေးဝယ်နိုင်ရန် ရေးသားထားပါသည်။ ဖုန်းတစ်လုံးရှိရုံဖြင့် ပစ္စည်းရွေး၊ စျေးကြည့်ပြီး ဆိုင် Viber / ဖုန်း သို့ အလွယ်တကူ အော်ဒါတင်နိုင်ပါသည်။';
    $savedSteps = $setting?->how_to_steps ?? [];
    $steps = count($savedSteps) > 0
        ? collect($savedSteps)->map(fn ($step) => [
            'icon' => $step['icon'] ?? '📌',
            'title' => $step['title'] ?? '',
            'desc' => $step['desc'] ?? '',
        ])->values()->all()
        : [
            [
                'icon' => '🔍',
                'title' => 'ပစ္စည်းရှာပါ',
                'desc' => 'မိမိလိုချင်သော ဖုန်းမော်ဒယ်၊ Touch LCD၊ ဘက်ထရီ၊ ဖုန်းကာဗာ သို့မဟုတ် CCTV ပစ္စည်းများကို ရှာဖွေပါ (ဖုန်းမှန်ရှာလိုပါက Glass Finder မှတစ်ဆင့် အလွယ်တကူ ရှာနိုင်ပါသည်)။',
            ],
            [
                'icon' => '👀',
                'title' => 'စျေးနှုန်းနဲ့ အချက်အလက်စစ်ပါ',
                'desc' => 'ပစ္စည်းပုံ၊ စျေးနှုန်း၊ လက်ကျန်ပစ္စည်း ရှိ/မရှိနှင့် အရောင်/ဆိုဒ် အမျိုးအစားများကို စစ်ဆေးကြည့်ရှုပါ။',
            ],
            [
                'icon' => '🛒',
                'title' => 'အော်ဒါစာရင်းထဲ ထည့်ပါ (+ Cart)',
                'desc' => 'ဝယ်ယူလိုသော ပစ္စည်းကို "+ Cart" (အော်ဒါစာရင်းထဲထည့်မည်) ခလုတ်နှိပ်ပါ။ လိုအပ်ပါက အရေအတွက် (+ / -) ပြင်ဆင်နိုင်ပါသည်။',
            ],
            [
                'icon' => '💬',
                'title' => 'ဆိုင်ကိုပို့ပြီး အတည်ပြုပါ',
                'desc' => 'မိမိ အမည်၊ ဖုန်းနံပါတ်၊ ပို့ရမည့် မြို့နယ်/လိပ်စာ ရိုက်ထည့်ပြီး "⚡ Viber ဖြင့် ပို့မည်" ကို နှိပ်၍ ဆိုင်ထံ တိုက်ရိုက် အော်ဒါပို့လိုက်ပါ။',
            ],
            [
                'icon' => '📦',
                'title' => 'ငွေပေးချေပြီး ပစ္စည်းလက်ခံပါ',
                'desc' => 'KBZPay, WavePay, ဘဏ်အကောင့် သို့မဟုတ် ကားဂိတ်/အိမ်အရောက် ငွေချေစနစ်ဖြင့် စိတ်ချစွာ ငွေပေးချေပြီး ပစ္စည်းလက်ခံရယူပါ။',
            ],
        ];

    $stepGradients = [
        'from-sky-500 to-blue-600',
        'from-violet-500 to-purple-600',
        'from-orange-500 to-amber-600',
        'from-pink-500 to-rose-600',
        'from-emerald-500 to-teal-600',
    ];

    $viberUrl = \App\Support\ContactLinkBuilder::viberChatUrl($setting?->viber_number);
    $viberIosUrl = \App\Support\ContactLinkBuilder::viberIosContactUrl($setting?->viber_number);
    $telegramUrl = \App\Support\ContactLinkBuilder::telegramUrl($setting?->telegram_username);
    $callNumber = $setting?->phone ? \App\Support\ContactLinkBuilder::normalizeMyanmarPhone($setting->phone) : null;
@endphp

<div class="mx-auto max-w-6xl space-y-8 sm:space-y-12 pb-12">
    {{-- ===================== Hero Header ===================== --}}
    <header class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-6">
        <div class="space-y-3">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black text-white uppercase shadow-2xs border-0"
                 style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;">
                <span>📖</span>
                <span>How to Order Guide · ဝယ်ယူနည်း လမ်းညွှန်</span>
            </div>

            <h1 class="font-sans text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 dark:text-white leading-tight">
                {{ __('messages.how_to_hero_title', ['count' => count($steps)]) }}
            </h1>

            <p class="max-w-3xl text-sm sm:text-base leading-relaxed text-slate-600 dark:text-slate-300 font-myanmar">
                @php
                    $rawIntro = $setting?->how_to_intro ?: $defaultIntro;
                    $introLead = trim((string) preg_replace('/\n\s*\d+[️⃣\.\)\s]\s*[^\n]*/', '', $rawIntro));
                @endphp
                {!! nl2br(e($introLead)) !!}
            </p>
        </div>

        {{-- Super Easy Tip Box for Beginners --}}
        <div class="rounded-2xl p-4 sm:p-5 border border-amber-200 dark:border-amber-900/60 bg-amber-50/80 dark:bg-amber-950/30 space-y-3 shadow-2xs">
            <div class="flex items-center gap-2">
                <span class="text-xl">💡</span>
                <h3 class="text-sm font-black text-amber-900 dark:text-amber-200 font-myanmar">
                    မှာယူရခက်နေပါသလား? အလွယ်ဆုံးနည်းလမ်းဖြင့် မှာယူနိုင်ပါသည်
                </h3>
            </div>
            <p class="text-xs sm:text-sm text-amber-800 dark:text-amber-300/90 leading-relaxed font-myanmar">
                ဝက်ဘ်ဆိုက်မှာ မမှာတတ်ပါက <strong>လိုချင်သော ပစ္စည်းကို ဖုန်းစခရင်ရှော့ (Screenshot) ရိုက်ပြီး</strong> အောက်ပါ Viber သို့မဟုတ် ဖုန်းဖြင့် တိုက်ရိုက် ဆက်သွယ် မေးမြန်းမှာယူနိုင်ပါသည် ခင်ဗျာ။
            </p>
            <div class="flex flex-wrap gap-2.5 pt-1">
                @if ($viberUrl)
                    <a href="{{ $viberUrl }}" data-ios-href="{{ $viberIosUrl }}"
                       style="background: linear-gradient(135deg, #7360F2 0%, #5f4de0 100%) !important; color: #ffffff !important;"
                       class="inline-flex min-h-[40px] items-center gap-2 rounded-full px-4 py-2 text-xs font-black text-white shadow-md shadow-purple-500/25 hover:brightness-110 active:scale-95 transition cursor-pointer select-none border-0">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-white/20">
                            <x-brand-icon brand="viber" class="h-3.5 w-3.5 fill-white text-white"/>
                        </span>
                        <span>Viber မှ ပုံပို့၍ မှာမည်</span>
                    </a>
                @endif
                @if ($callNumber)
                    <a href="tel:{{ $callNumber }}"
                       style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: #ffffff !important;"
                       class="inline-flex min-h-[40px] items-center gap-2 rounded-full px-4 py-2 text-xs font-black text-white shadow-md shadow-emerald-500/25 hover:brightness-110 active:scale-95 transition cursor-pointer select-none border-0">
                        <span>📞</span>
                        <span>ဖုန်းတိုက်ရိုက်ခေါ်မည် ({{ $callNumber }})</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Action Shortcut Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
            <a href="{{ $productsUrl }}" class="group rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/60 p-4 transition hover:border-sky-400 hover:shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-sky-100 dark:bg-sky-950 text-sky-600 dark:text-sky-400 flex items-center justify-center text-lg shrink-0">🛍️</span>
                    <div>
                        <span class="block text-xs font-black text-slate-900 dark:text-white group-hover:text-sky-600 transition">ပစ္စည်းများ ရှာဖွေမည်</span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400">Mobile, CCTV, Computer & More</span>
                    </div>
                </div>
            </a>
            <a href="{{ $glassFinderUrl }}" class="group rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/60 p-4 transition hover:border-purple-400 hover:shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg shrink-0">🔍</span>
                    <div>
                        <span class="block text-xs font-black text-slate-900 dark:text-white group-hover:text-purple-600 transition">ဖုန်းမှန်ကုဒ် ရှာဖွေမည်</span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400">Glass Finder ဖုန်းမော်ဒယ်ရိုက်ရှာရန်</span>
                    </div>
                </div>
            </a>
            <a href="{{ $orderBuilderUrl }}" class="group rounded-2xl border border-orange-200/80 dark:border-orange-900/60 bg-orange-50/40 dark:bg-orange-950/20 p-4 transition hover:border-orange-400 hover:shadow-sm">
                <div class="flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-900/60 text-orange-600 dark:text-orange-400 flex items-center justify-center text-lg shrink-0">⚡</span>
                    <div>
                        <span class="block text-xs font-black text-slate-900 dark:text-white group-hover:text-orange-600 transition">Order Builder / စာရင်း</span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400">ထည့်ထားသော ပစ္စည်းများ စစ်ဆေးရန်</span>
                    </div>
                </div>
            </a>
        </div>
    </header>

    {{-- ===================== 5 Steps Visual Guide ===================== --}}
    <section class="space-y-6">
        <div class="space-y-1">
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-black tracking-wide text-white uppercase shadow-2xs border-0"
                 style="background: linear-gradient(135deg, #7360F2 0%, #5f4de0 100%) !important;">
                <span>✨</span>
                <span>Step by Step Instructions</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-sans">
                အလွယ်တကူ မှာယူနိုင်သော အဆင့် (၅) ဆင့်
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-myanmar">
                အောက်ပါ အဆင့်များကို တစ်ဆင့်ချင်း အစဉ်လိုက် ကြည့်ရှုပြီး စျေးဝယ်နိုင်ပါသည်
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            @foreach ($steps as $i => $step)
                <div class="rounded-3xl border border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 flex flex-col justify-between shadow-2xs hover:shadow-md hover:border-sky-400 dark:hover:border-sky-600 transition space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="w-12 h-12 rounded-2xl bg-gradient-to-br {{ $stepGradients[$i % count($stepGradients)] }} text-white text-xl flex items-center justify-center shadow-md">
                                {{ $step['icon'] }}
                            </span>
                            <span class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono font-black text-xs flex items-center justify-center">
                                {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>

                        <h3 class="font-black text-sm text-slate-900 dark:text-white leading-snug">
                            {{ $step['title'] }}
                        </h3>

                        <p class="text-xs text-slate-600 dark:text-slate-400 font-myanmar leading-relaxed">
                            {{ $step['desc'] }}
                        </p>
                    </div>

                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80">
                        <span class="text-[10px] font-black text-sky-600 dark:text-sky-400 uppercase">
                            အဆင့် ({{ $i + 1 }})
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ===================== Notes Strip ===================== --}}
    <section class="rounded-3xl bg-slate-50 dark:bg-slate-800/60 p-6 border border-slate-200/90 dark:border-slate-700/80 space-y-4">
        <div class="flex items-center gap-2">
            <span class="text-base">📌</span>
            <h2 class="text-sm font-black text-slate-900 dark:text-white font-myanmar">
                မှာယူရာတွင် သတိပြုရန်နှင့် သိမှတ်ဖွယ်ရာများ
            </h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-1">
                <span class="text-base">📸</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">မသေချာပါက Screenshot ပို့ပါ</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">ဖုန်းမော်ဒယ် သို့မဟုတ် ပစ္စည်းပုံကို screenshot ရိုက်၍ Viber မှတဆင့် လွတ်လပ်စွာ မေးမြန်းနိုင်ပါသည်။</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-1">
                <span class="text-base">₭</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">စျေးနှုန်းနှင့် Stock ပြန်စစ်ပေးခြင်း</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">အော်ဒါပို့ပြီးပါက ဆိုင်ဘက်မှ လက်ကျန်ပစ္စည်းနှင့် ကာလပေါက်စျေးနှုန်းကို ချက်ချင်း အတည်ပြုပေးပါမည်။</p>
            </div>
            <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-2xs space-y-1">
                <span class="text-base">🚚</span>
                <p class="text-xs font-bold text-slate-800 dark:text-slate-200">နယ်ဝေး ကားဂိတ်/အိမ်အရောက် ပို့ဆောင်မှု</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-myanmar leading-relaxed">မြန်မာတစ်နိုင်ငံလုံး မြို့နယ်အလိုက် သင့်တော်သော အမြန်ချောပို့ သို့မဟုတ် ကားဂိတ်ဖြင့် ပို့ဆောင်ပေးပါသည်။</p>
            </div>
        </div>
    </section>

    {{-- ===================== Contact, Payment & Delivery 3 Columns ===================== --}}
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Contact Column --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 p-6 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4 flex flex-col">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 flex items-center justify-center text-sm">💬</span>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">ဆိုင်နှင့် တိုက်ရိုက်ဆက်သွယ်ရန်</h2>
            </div>
            <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-400 font-myanmar">
                {{ __('messages.how_to_order_contact_hint') }}
            </p>
            <div class="space-y-2 pt-1 mt-auto">
                @if ($callNumber)
                    <a href="tel:{{ $callNumber }}"
                       style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: #ffffff !important;"
                       class="w-full min-h-[42px] inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-black text-white shadow-md shadow-emerald-500/20 hover:brightness-110 active:scale-95 transition cursor-pointer select-none border-0">
                        <span>📞</span>
                        <span>ဖုန်းခေါ်မည် ({{ $callNumber }})</span>
                    </a>
                @endif
                @if ($viberUrl)
                    <a href="{{ $viberUrl }}" data-ios-href="{{ $viberIosUrl }}"
                       style="background: linear-gradient(135deg, #7360F2 0%, #5f4de0 100%) !important; color: #ffffff !important;"
                       class="w-full min-h-[42px] inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-black text-white shadow-md shadow-purple-500/20 hover:brightness-110 active:scale-95 transition cursor-pointer select-none border-0">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-white/20">
                            <x-brand-icon brand="viber" class="h-3.5 w-3.5 fill-white text-white"/>
                        </span>
                        <span>Viber Chat မေးမြန်းမည်</span>
                    </a>
                @endif
                @if ($telegramUrl)
                    <a href="{{ $telegramUrl }}" target="_blank" rel="noopener noreferrer"
                       style="background: linear-gradient(135deg, #229ED9 0%, #0284c7 100%) !important; color: #ffffff !important;"
                       class="w-full min-h-[42px] inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-black text-white shadow-md shadow-sky-500/20 hover:brightness-110 active:scale-95 transition cursor-pointer select-none border-0">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-white/20">
                            <x-brand-icon brand="telegram" class="h-3.5 w-3.5 fill-white text-white"/>
                        </span>
                        <span>Telegram Chat မေးမြန်းမည်</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- Payment Column --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 p-6 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 flex items-center justify-center text-sm">💵</span>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">ငွေပေးချေနည်းများ</h2>
            </div>
            @php $activePayments = $store?->paymentMethods()->active()->get(); @endphp
            @if ($activePayments && $activePayments->isNotEmpty())
                <div class="space-y-2">
                    @foreach ($activePayments as $pm)
                        <button type="button"
                            @click="$dispatch('open-payment-modal', {
                                name: @js($pm->name),
                                qr_url: @js($pm->qrUrl()),
                                account_name: @js($pm->show_account_details ? $pm->account_name : null),
                                account_number: @js($pm->show_account_details ? $pm->account_number : null),
                                instructions: @js($pm->instructions),
                            })"
                            class="w-full flex items-center justify-between gap-3 rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-800/40 p-3 hover:border-violet-300 dark:hover:border-violet-700 hover:shadow-xs transition active:scale-[0.99] cursor-pointer text-left group">
                            <div class="flex items-center gap-3 min-w-0">
                                <x-payment-method-icon :method="$pm" class="h-9 w-9 shrink-0 group-hover:scale-105 transition-transform" />
                                <span class="text-xs sm:text-sm font-extrabold text-slate-900 dark:text-white truncate">{{ $pm->name }}</span>
                            </div>
                            <span class="shrink-0 px-2.5 py-1 rounded-xl bg-violet-50 group-hover:bg-violet-100 dark:bg-violet-950/50 dark:group-hover:bg-violet-900/60 border border-violet-200 dark:border-violet-800 text-violet-700 dark:text-violet-300 text-xs font-bold transition flex items-center gap-1">
                                <span>{{ $pm->hasQr() ? '📱 QR Code' : 'အချက်အလက်' }}</span>
                                <span class="text-[11px]">→</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            @elseif ($setting?->payment_info)
                <div class="rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 p-3.5">
                    <p class="whitespace-pre-line text-xs leading-relaxed text-slate-600 dark:text-slate-400 font-myanmar">{{ $setting->payment_info }}</p>
                </div>
            @else
                <div class="space-y-2">
                    <div class="flex items-center gap-2 p-2.5 rounded-xl bg-sky-50 dark:bg-sky-950/40 text-sky-800 dark:text-sky-300 text-xs font-black">
                        <span>📱</span> KBZPay / WavePay / AYA Pay
                    </div>
                    <div class="flex items-center gap-2 p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 text-xs font-black">
                        <span>💵</span> Cash On Delivery (အိမ်ရောက်ငွေချေ)
                    </div>
                </div>
            @endif
        </div>

        {{-- Address & Delivery Column --}}
        <div class="rounded-3xl bg-white dark:bg-slate-900 p-6 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 flex items-center justify-center text-sm">🚚</span>
                <h2 class="text-sm font-black text-slate-900 dark:text-white">ဆိုင်လိပ်စာ / ပို့ဆောင်ရေး</h2>
            </div>
            <div class="space-y-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400 font-myanmar">
                <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 space-y-1">
                    <p class="flex items-start gap-2 font-bold text-slate-800 dark:text-slate-200">
                        <span>📍</span>
                        <span>{{ $setting?->address ?? 'DataPOS' }}</span>
                    </p>
                    <p class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                        <span>🕒</span>
                        <span>{{ $setting?->opening_hours ?: '9:00AM to 5:30PM' }}</span>
                    </p>
                </div>

                @php $activeDeliveries = $store?->deliveryMethods()->active()->get(); @endphp
                @if ($activeDeliveries && $activeDeliveries->isNotEmpty())
                    <div class="space-y-2 pt-1">
                        @foreach ($activeDeliveries as $dm)
                            <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3 dark:border-slate-800 dark:bg-slate-800/40">
                                <p class="text-xs font-black text-slate-900 dark:text-white">{{ $dm->icon ?: '🚚' }} {{ $dm->name }}
                                    @if ($dm->estimated_time) <span class="font-bold text-sky-700 dark:text-sky-300">· {{ $dm->estimated_time }}</span> @endif
                                </p>
                                @if ($dm->fee_note || $dm->service_area)
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ collect([$dm->service_area, $dm->fee_note])->filter()->implode(' · ') }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif ($setting?->delivery_info)
                    <div class="rounded-2xl border border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 p-3">
                        <p class="whitespace-pre-line text-xs leading-relaxed text-slate-600 dark:text-slate-400 font-myanmar">{{ $setting->delivery_info }}</p>
                    </div>
                @else
                    <p class="pt-1 text-[11px] text-slate-500">မြန်မာတစ်နိုင်ငံလုံး အိမ်အရောက် သို့မဟုတ် ကားဂိတ်ဖြင့် ပို့ဆောင်ပေးပါသည်။</p>
                @endif
            </div>
        </div>
    </section>

    {{-- ===================== Google Maps Location Section ===================== --}}
    @if ($setting?->map_enabled && ($setting->mapUrl() || $setting?->address))
    <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 sm:p-8 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="space-y-1">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-black tracking-wide text-white uppercase shadow-2xs border-0"
                     style="background: linear-gradient(135deg, #e11d48 0%, #f43f5e 100%) !important;">
                    <span>📍</span>
                    <span>Store Location</span>
                </div>
                <h2 class="font-sans text-xl font-black text-slate-900 dark:text-white">
                    {{ $setting->map_title ?: __('messages.visit_our_store') }}
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-myanmar">
                    📍 {{ $setting?->address }} · 🕒 {{ $setting?->opening_hours ?: '9:00AM to 5:30PM' }}
                </p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                @if ($setting->mapUrl())
                    <a href="{{ $setting->mapUrl() }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 px-4 py-2 text-xs font-black text-white transition active:scale-95 shadow-sm">
                        <svg class="h-3.5 w-3.5 text-red-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                        <span>{{ __('messages.open_in_google_maps') }}</span>
                    </a>
                @endif
                @if ($setting->mapDirectionsUrl())
                    <a href="{{ $setting->mapDirectionsUrl() }}" target="_blank" rel="noopener noreferrer"
                       style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important; color: #ffffff !important;"
                       class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-black text-white shadow-sm hover:brightness-110 active:scale-95 transition border-0">
                        <span>🧭</span>
                        <span>{{ __('messages.get_directions') }}</span>
                    </a>
                @endif
            </div>
        </div>

        @if ($setting->map_embed_enabled && $setting->mapEmbedSrc())
            <div class="w-full overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 aspect-video max-h-[360px] shadow-sm">
                <iframe class="w-full h-full border-0" src="{{ $setting->mapEmbedSrc() }}" title="{{ $setting->map_title ?: __('messages.visit_our_store') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
        @endif
    </section>
    @endif

    {{-- ===================== Video Tutorials ===================== --}}
    @php
        $videos = $setting?->how_to_videos ?? [];
        $channelLinks = array_values(array_filter([
            ['url' => $setting?->youtube_url, 'label' => __('messages.watch_on_youtube'), 'class' => 'bg-red-600 hover:bg-red-500'],
            ['url' => $setting?->tiktok_url, 'label' => __('messages.watch_on_tiktok'), 'class' => 'bg-slate-900 hover:bg-slate-700'],
            ['url' => $setting?->facebook_url, 'label' => __('messages.watch_on_facebook'), 'class' => 'bg-blue-600 hover:bg-blue-500'],
        ], fn ($c) => !empty($c['url'])));
    @endphp

    @if (count($videos) > 0 || count($channelLinks) > 0)
        <section class="rounded-3xl bg-white dark:bg-slate-900 p-6 sm:p-8 border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-6">
            <div class="space-y-1 text-center">
                <h2 class="font-sans text-xl font-black text-slate-900 dark:text-white sm:text-2xl">
                    {{ __('messages.video_tutorials') }}
                </h2>
                <p class="mx-auto max-w-xl text-xs sm:text-sm leading-relaxed text-slate-500 dark:text-slate-400 font-myanmar">
                    {{ __('messages.video_tutorials_hint') }}
                </p>
            </div>

            @if (count($videos) > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($videos as $video)
                        @php
                            $videoUrl = $video['url'] ?? '';
                            $videoTitle = $video['title'] ?? null;
                            $ytId = preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/', $videoUrl, $m) ? $m[1] : null;
                            $isTikTok = str_contains($videoUrl, 'tiktok.com');
                        @endphp
                        @if ($ytId)
                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 shadow-2xs">
                                <div class="relative aspect-video w-full bg-black">
                                    <iframe class="absolute inset-0 h-full w-full" src="https://www.youtube.com/embed/{{ $ytId }}" title="{{ $videoTitle ?? __('messages.video_tutorials') }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                                </div>
                                @if ($videoTitle)
                                    <p class="px-4 py-3 text-xs font-black text-slate-800 dark:text-slate-200">{{ $videoTitle }}</p>
                                @endif
                            </div>
                        @else
                            <a href="{{ $videoUrl }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-sky-300 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-sky-700">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $isTikTok ? 'bg-slate-900' : 'bg-rose-600' }} text-xl text-white shadow-md">🎬</span>
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
                <div class="flex flex-wrap items-center justify-center gap-2 pt-2">
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
