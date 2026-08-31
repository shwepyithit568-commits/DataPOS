@extends('layouts.admin.app')

@section('title', __('messages.settings_storefront_settings') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2 sm:p-3')

@section('content')
@php
    $inputClass = 'mt-1 block w-full rounded-xl border border-slate-200 dark:border-slate-700 px-3.5 py-2.5 bg-slate-50/50 dark:bg-slate-800 text-xs sm:text-sm text-slate-800 dark:text-slate-100 shadow-xs focus:border-blue-500 focus:bg-white dark:focus:bg-slate-900 focus:ring-2 focus:ring-blue-500/20 transition';
    $labelClass = 'block text-xs font-bold text-slate-700 dark:text-slate-200';
    $helpClass = 'mt-1 text-[11px] text-slate-500 dark:text-slate-400';

    $settingsBase = url('/store/' . $store->slug . '/admin/settings');
    $sections = [
        'general'      => ['label' => __('messages.settings_general'),        'icon' => '🏪', 'badge' => 'Profile',  'desc' => 'Name, logo, tagline & language',      'url' => $settingsBase],
        'currency'     => ['label' => 'Currency Format',                      'icon' => '💱', 'badge' => 'Money',    'desc' => 'Currency symbol, decimals & accounting format', 'url' => $settingsBase . '/currency'],
        'pos'          => ['label' => __('messages.settings_pos'),            'icon' => '🛒', 'badge' => 'Counter',  'desc' => 'Cashier held sales & PIN controls',    'url' => $settingsBase . '/pos'],
        'appearance'   => ['label' => __('messages.settings_appearance'),     'icon' => '🎨', 'badge' => 'Branding', 'desc' => 'Brand colors, themes & banners',       'url' => $settingsBase . '/appearance'],
        'contact'      => ['label' => __('messages.settings_contact'),        'icon' => '☎️', 'badge' => 'Channels', 'desc' => 'Phones, Viber, Telegram & chats',      'url' => $settingsBase . '/contact'],
        'delivery'     => ['label' => __('messages.settings_delivery'),       'icon' => '🚚', 'badge' => 'Checkout', 'desc' => 'Delivery zones & payment methods',     'url' => $settingsBase . '/delivery'],
        'how-to-order' => ['label' => __('messages.settings_how_to_order'),   'icon' => '📖', 'badge' => 'Guide',    'desc' => 'Order tutorial & step instructions',   'url' => $settingsBase . '/how-to-order'],
        'footer'       => ['label' => __('messages.settings_footer'),         'icon' => '🖥️', 'badge' => 'Preview',  'desc' => 'Live customer footer layout preview',  'url' => $settingsBase . '/footer'],
    ];

    $sectionTitles = [
        'general'      => __('messages.settings_store_identity'),
        'currency'     => 'Currency & Accounting Format',
        'pos'          => __('messages.settings_pos'),
        'appearance'   => __('messages.settings_appearance'),
        'contact'      => __('messages.settings_contact_social'),
        'delivery'     => __('messages.settings_delivery'),
        'how-to-order' => __('messages.settings_how_to_order_page'),
        'footer'       => __('messages.settings_footer_page'),
    ];

    // Blade partial names cannot contain hyphens — map the route segment.
    $sectionPartial = $section === 'how-to-order' ? 'how_to_order' : $section;

    $sectionDescs = [
        'general'      => $store->name . ' ၏ storefront header, ဆိုင်အမည်၊ Logo နှင့် အဓိကဘာသာစကား သတ်မှတ်ချက်များ။',
        'currency'     => 'စနစ်တစ်ခုလုံးရှိ ငွေကြေးသင်္ကေတ (Ks, $), သင်္ကေတနေရာ (100,000 Ks vs Ks 100,000), ဒသမ (.00) နှင့် စာရင်းကိုင် accounting format သတ်မှတ်ချက်များ။',
        'pos'          => 'POS ဆိုင် cashier အတွေ့အကြုံ — thermal voucher, shift, held sale, pin control ဆက်တင်များ။',
        'appearance'   => 'Storefront ၏ Brand Color, Button Color, Header Background တို့ကို ထိန်းချုပ်ပါ — preset palette ဒါမှမဟုတ် custom HEX color ရွေးချယ်နိုင်ပါသည်။',
        'contact'      => 'Storefront footer နှင့် order confirmation တွင်ပြမည့် phone, Viber, Telegram, social media data များ။',
        'delivery'     => 'Storefront footer တွင်ပြမည့် delivery area, payment method, ကြော်ညာ စာသားများ။',
        'how-to-order' => 'Storefront ရဲ့ "မှာယူနည်း" စာမျက်နှာမှာ ပြမည့် မိတ်ဆက်စာသား၊ အဆင့်တွေနဲ့ ဗီဒီယိုလင့်များ။',
        'footer'       => 'Storefront footer တစ်ခုလုံးကို စုစည်း preview — customer တွေ မြင်ရတဲ့ပုံ အတိအကျ။',
    ];
@endphp

<div class="w-full space-y-3 sm:space-y-4">
    {{-- 1. Modern Page Header (Preserves admin-page-title for testing compatibility) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-3.5 sm:p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200/60 dark:border-blue-800/60 text-blue-600 dark:text-blue-400 grid place-items-center text-lg sm:text-xl font-bold shadow-sm shrink-0">
                ⚙️
            </div>
            <div class="min-w-0">
                <h1 class="admin-page-title text-base sm:text-xl font-black text-slate-900 dark:text-white flex items-center gap-2 truncate">
                    <span>{{ __('messages.settings_storefront_settings') }}</span>
                </h1>
                <p class="admin-page-sub text-xs text-slate-500 dark:text-slate-400 truncate">{{ $sectionDescs[$section] ?? ($store->name . ' Store Configuration') }}</p>
            </div>
        </div>

        {{-- Top Right Actions --}}
        <div class="flex items-center gap-2 self-start sm:self-auto shrink-0 flex-wrap">
            <a href="{{ url('/store/' . $store->slug) }}" target="_blank" rel="noopener noreferrer"
               class="px-3.5 py-2 rounded-xl text-xs font-black bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 shadow-sm active:scale-95">
                <span>👁️</span>
                <span>{{ __('messages.settings_view_storefront') }}</span>
            </a>
        </div>
    </div>

    {{-- Flash & Error Alerts --}}
    @if (session('success'))
        <div class="p-3.5 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-2xl text-xs font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-2.5 shadow-sm">
            <span class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900 grid place-items-center text-emerald-600 dark:text-emerald-300 font-black text-xs shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-2xl text-xs font-bold text-rose-700 dark:text-rose-300 space-y-1 shadow-sm">
            <p class="font-black flex items-center gap-1.5">
                <span>⚠️</span>
                <span>{{ __('messages.settings_check_fields') }}</span>
            </p>
            @foreach ($errors->all() as $error)
                <p class="text-[11px] font-medium">• {{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- 2. 4 Overview Status Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3.5 sm:p-4 shadow-sm flex items-center justify-between transition hover:shadow">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-0.5 truncate">{{ __('messages.settings_overview_store_identity') }}</p>
                <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white truncate">{{ $setting->store_name ?? $store->name }}</h3>
                <p class="text-[10px] text-slate-400 font-medium truncate mt-0.5">{{ $setting->tagline ?: __('messages.settings_overview_no_tagline') }}</p>
            </div>
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 grid place-items-center text-base sm:text-lg font-bold shadow-inner shrink-0">
                🏪
            </div>
        </div>

        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3.5 sm:p-4 shadow-sm flex items-center justify-between transition hover:shadow">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-0.5 truncate">{{ __('messages.settings_overview_brand_theme') }}</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="w-3.5 h-3.5 rounded-full ring-2 ring-white dark:ring-slate-900 shadow-xs" style="background-color: {{ $setting->brand_color ?: '#0284c7' }};"></span>
                    <h3 class="text-xs sm:text-sm font-black font-mono text-slate-900 dark:text-white">{{ strtoupper($setting->brand_color ?: '#0284C7') }}</h3>
                </div>
                <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium truncate mt-0.5">{{ __('messages.settings_overview_active_palette') }}</p>
            </div>
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 grid place-items-center text-base sm:text-lg font-bold shadow-inner shrink-0">
                🎨
            </div>
        </div>

        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3.5 sm:p-4 shadow-sm flex items-center justify-between transition hover:shadow">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-0.5 truncate">{{ __('messages.settings_overview_direct_contact') }}</p>
                <h3 class="text-xs sm:text-sm font-black font-mono text-indigo-600 dark:text-indigo-400 truncate">{{ $setting->phone ?: __('messages.settings_overview_no_phone') }}</h3>
                <p class="text-[10px] text-slate-400 font-medium truncate mt-0.5">{{ $setting->telegram_username ? ('TG: @' . ltrim($setting->telegram_username, '@')) : 'Viber / Telegram' }}</p>
            </div>
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 grid place-items-center text-base sm:text-lg font-bold shadow-inner shrink-0">
                ☎️
            </div>
        </div>

        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3.5 sm:p-4 shadow-sm flex items-center justify-between transition hover:shadow">
            <div class="min-w-0">
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-0.5 truncate">{{ __('messages.settings_overview_delivery_pay') }}</p>
                <h3 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white truncate">{{ __('messages.settings_overview_active_methods') }}</h3>
                <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium truncate mt-0.5">{{ __('messages.settings_overview_ready_orders') }}</p>
            </div>
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 grid place-items-center text-base sm:text-lg font-bold shadow-inner shrink-0">
                🚚
            </div>
        </div>
    </div>

    {{-- 3. Horizontal Scroll Navigation Tabs --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-2xl p-1.5 sm:p-2 shadow-sm">
        <nav class="flex items-center gap-1.5 overflow-x-auto scroll-smooth py-0.5 px-0.5"
             aria-label="Store settings sections"
             style="scrollbar-width: thin;">
            @foreach ($sections as $key => $sec)
                @php
                    $isActive = ($section === $key);
                @endphp
                <a href="{{ $sec['url'] }}"
                   @if ($isActive) aria-current="page" @endif
                   class="flex items-center gap-2 px-3 sm:px-3.5 py-2 rounded-xl text-xs whitespace-nowrap shrink-0 transition group {{ $isActive ? 'bg-blue-600 text-white font-black shadow-sm shadow-blue-500/20 ring-1 ring-blue-600' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold' }}">
                    <span class="text-sm sm:text-base shrink-0">{{ $sec['icon'] }}</span>
                    <span>{{ $sec['label'] }}</span>
                    <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-md shrink-0 {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300' }}">
                        {{ $sec['badge'] }}
                    </span>
                </a>
            @endforeach
        </nav>
    </div>

    {{-- 4. Full-Width Form Panel for Active Section --}}
    <main class="w-full">
        @if ($section === 'appearance')
            {{-- Appearance uses the fetch-based Draft API (AppearanceDraftController)
                 with its own Save Draft / Publish Live buttons. It is deliberately
                 NOT wrapped in the settings <form>: an Enter-key submission inside
                 the colour inputs must never reach the legacy direct-publish route
                 and bypass the draft conflict checks. --}}
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                @include('admin.settings.sections.' . $sectionPartial)
            </div>
        @elseif ($section !== 'delivery' && $section !== 'footer')
            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/settings') }}" enctype="multipart/form-data"
                  class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden"
                  x-data="{ submitting: false }"
                  @submit="submitting = true; $dispatch('settings-submitting')">
                @csrf
                <input type="hidden" name="section" value="{{ $section }}">

                <div class="p-4 sm:p-6">
                    @include('admin.settings.sections.' . $sectionPartial)
                </div>

                {{-- Form Footer Action Bar for standard settings sections --}}
                <div class="border-t border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 px-4 py-3.5 sm:px-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        {{ __('messages.settings_fields_note') }}
                    </p>
                    <button type="submit" :disabled="submitting"
                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-500 px-5 py-2.5 text-xs font-black text-white shadow-sm shadow-blue-500/20 transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:cursor-not-allowed disabled:opacity-70 active:scale-95">
                        <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-show="submitting" x-cloak>{{ __('messages.settings_saving') }}</span>
                        <span x-show="!submitting">💾 {{ __('messages.save') . ' ' . $sectionTitles[$section] }}</span>
                    </button>
                </div>
            </form>
        @elseif ($section === 'footer')
            <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 sm:p-6">
                @include('admin.settings.sections.footer')
            </div>
        @else
            <div class="space-y-4">
                @include('admin.settings.sections.delivery')
            </div>
        @endif
    </main>

    @if ($section === 'appearance')
        <section class="w-full rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5 space-y-3" aria-labelledby="theme-history-title">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 grid place-items-center text-sm font-bold">
                        🕒
                    </span>
                    <div>
                        <h2 id="theme-history-title" class="text-sm font-black text-slate-900 dark:text-white">{{ __('messages.theme_history_title') }}</h2>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ __('messages.theme_history_desc') }}</p>
                    </div>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 self-start sm:self-auto">
                    {{ __('messages.theme_history_latest_10') }}
                </span>
            </div>

            @if ($themeRevisions->isEmpty())
                <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 px-4 py-8 text-center text-xs text-slate-500 dark:text-slate-400">
                    {{ __('messages.theme_history_empty') }}
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800">
                    <table class="min-w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-[11px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="px-3.5 py-2.5">{{ __('messages.theme_history_th_revision') }}</th>
                                <th class="px-3.5 py-2.5">{{ __('messages.theme_history_th_palette') }}</th>
                                <th class="px-3.5 py-2.5">{{ __('messages.theme_history_th_action') }}</th>
                                <th class="px-3.5 py-2.5">{{ __('messages.theme_history_th_published_by') }}</th>
                                <th class="px-3.5 py-2.5">{{ __('messages.theme_history_th_datetime') }}</th>
                                <th class="px-3.5 py-2.5 text-right font-black">{{ __('messages.theme_history_th_restore') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($themeRevisions as $index => $revision)
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                                    <td class="whitespace-nowrap px-3.5 py-3 font-mono font-bold text-slate-800 dark:text-slate-100">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-xs border border-slate-200/60 dark:border-slate-700">
                                                #{{ $revision->revision_number }}
                                            </span>
                                            @if ($index === 0)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    {{ __('messages.theme_history_current') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-3 font-bold text-slate-700 dark:text-slate-200">
                                        {{ str($revision->theme_config['theme_preset'] ?? 'default')->replace('_', ' ')->title() }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-3 text-slate-600 dark:text-slate-300">
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold {{ $revision->action === 'rollback' ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300' }}">
                                            {{ ucfirst($revision->action) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-3 text-slate-600 dark:text-slate-300">
                                        {{ $revision->actor?->name ?? 'System' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-3 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ $revision->created_at?->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-3.5 py-3 text-right">
                                        @if ($index !== 0)
                                            <form method="POST" action="{{ route('store.admin.settings.appearance.rollback', ['store_slug' => $store->slug, 'revision' => $revision->id]) }}">
                                                @csrf
                                                <button type="submit" class="min-h-8 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-1 text-xs font-black text-slate-700 dark:text-slate-200 transition hover:border-violet-400 hover:text-violet-700 dark:hover:border-violet-500 dark:hover:text-violet-300 shadow-xs cursor-pointer active:scale-95">
                                                    {{ __('messages.theme_history_btn_restore') }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs font-black text-emerald-600 dark:text-emerald-400">✓ {{ __('messages.theme_history_active') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</div>
@endsection
