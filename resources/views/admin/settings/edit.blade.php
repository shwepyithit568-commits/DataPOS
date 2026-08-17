@extends('layouts.admin.app')

@section('content')
@php
    $inputClass = 'mt-1 block w-full rounded-xl border border-gray-300 dark:border-slate-600 px-3.5 py-3 bg-white dark:bg-slate-900 text-sm text-gray-900 dark:text-slate-100 shadow-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30';
    $labelClass = 'block text-sm font-semibold text-gray-800 dark:text-slate-200';
    $helpClass = 'mt-1 text-xs text-gray-500 dark:text-slate-400';

    $settingsBase = url('/store/' . $store->slug . '/admin/settings');
    $sections = [
        'general'      => ['label' => __('messages.settings_general'),        'icon' => 'general',  'url' => $settingsBase],
        'contact'      => ['label' => __('messages.settings_contact'),        'icon' => 'contact',  'url' => $settingsBase . '/contact'],
        'delivery'     => ['label' => __('messages.settings_delivery'),       'icon' => 'delivery', 'url' => $settingsBase . '/delivery'],
        'how-to-order' => ['label' => __('messages.settings_how_to_order'),   'icon' => 'guide',    'url' => $settingsBase . '/how-to-order'],
        'footer'       => ['label' => __('messages.settings_footer'),         'icon' => 'footer',   'url' => $settingsBase . '/footer'],
        'pos'          => ['label' => __('messages.settings_pos'),            'icon' => 'pos',      'url' => $settingsBase . '/pos'],
    ];

    $sectionTitles = [
        'general'      => __('messages.settings_store_identity'),
        'contact'      => __('messages.settings_contact_social'),
        'delivery'     => __('messages.settings_delivery'),
        'how-to-order' => __('messages.settings_how_to_order_page'),
        'footer'       => __('messages.settings_footer_page'),
        'pos'          => __('messages.settings_pos'),
    ];

    // Blade partial names cannot contain hyphens — map the route segment.
    $sectionPartial = $section === 'how-to-order' ? 'how_to_order' : $section;

    $sectionDescs = [
        'general'      => $store->name . ' ၏ storefront header, title နှင့် default language အတွက်အချက်အလက်များ။',
        'contact'      => 'Storefront footer နှင့် order confirmation တွင်ပြမည့် phone, Viber, Telegram, social media data များ။',
        'delivery'     => 'Storefront footer တွင်ပြမည့် delivery area, payment method, ကြော်ညာ စာသားများ။',
        'how-to-order' => 'Storefront ရဲ့ "မှာယူနည်း" စာမျက်နှာမှာ ပြမည့် မိတ်ဆက်စာသား၊ အဆင့်တွေနဲ့ ဗီဒီယိုလင့်များ။',
        'footer'       => 'Storefront footer တစ်ခုလုံးကို စုစည်း preview — customer တွေ မြင်ရတဲ့ပုံ အတိအကျ။',
        'pos'          => 'POS ဆိုင် cashier အတွေ့အကြုံ — held sale တွေကို ဘယ်နှစ်နာရီကြာရင် အလိုအလျောက် သက်တမ်းကုန်မလဲ။',
    ];
@endphp

<div class="w-full space-y-6">
    {{-- Shared clean page header (matches Products / Brands / Categories) --}}
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">{{ __('messages.settings_storefront_settings') }}</h1>
            <p class="admin-page-sub">{{ $sectionDescs[$section] }}</p>
        </div>
        <a href="{{ url('/store/' . $store->slug) }}"
            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-violet-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            {{ __('messages.settings_view_storefront') }}
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-700 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300">
            <p class="font-bold">{{ __('messages.settings_check_fields') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[13rem_minmax(0,1fr)] lg:items-start">
        {{-- Section nav: borderless sticky column on desktop, horizontal tabs on mobile --}}
        <aside class="lg:sticky lg:top-4">
            <p class="px-1 pb-2 text-xs font-black uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ __('messages.settings') }}</p>
            <nav class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1 lg:mx-0 lg:block lg:space-y-1 lg:overflow-visible lg:px-0 lg:pb-0" aria-label="Store settings sections">
                @foreach ($sections as $key => $sec)
                    <a href="{{ $sec['url'] }}"
                        @if ($section === $key) aria-current="page" @endif
                        class="flex min-w-max items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-bold transition lg:min-w-0 {{ $section === $key ? 'bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $section === $key ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}" aria-hidden="true">
                            @switch($sec['icon'])
                                @case('contact')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.5 5.5 9 8l-1.5 2a10 10 0 0 0 6.5 6.5l2-1.5 2.5 2.5-1.5 3A16 16 0 0 1 3.5 7l3-1.5Z"/></svg>
                                    @break
                                @case('delivery')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h11v9H3V7Zm11 3h4l3 3v3h-7v-6ZM7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
                                    @break
                                @case('guide')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H7a3 3 0 0 0-3 3V5.5Zm0 0V22m4-14h8m-8 4h8"/></svg>
                                    @break
                                @case('footer')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v9H4V4Zm0 11h16v5H4v-5ZM7 7.5h2m-2 2.5h2m6-2.5h2m-2 2.5h2"/></svg>
                                    @break
                                @case('pos')
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18v12H3V5Zm2 3h14v6H5V8Zm3 9v2m8-2v2"/></svg>
                                    @break
                                @default
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M7 7v10M12 7v10M17 7v10M4 17h16"/></svg>
                            @endswitch
                        </span>
                        <span class="truncate whitespace-nowrap">{{ $sec['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        {{-- Section form (each section saves only its own fields) --}}
        {{-- The delivery section manages its own standalone forms (payment/delivery
             method CRUD + legacy notes), so it must NOT be nested inside this outer
             form — nested <form> elements are invalid HTML and browsers drop them.
             The footer section is a read-only preview page — no form at all. --}}
        @if ($section !== 'delivery' && $section !== 'footer')
            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/settings') }}" enctype="multipart/form-data"
                class="admin-panel overflow-hidden"
                x-data="{ submitting: false }"
                @submit="submitting = true; $dispatch('settings-submitting')">
                @csrf
                <input type="hidden" name="section" value="{{ $section }}">

                @include('admin.settings.sections.' . $sectionPartial)

                <div class="border-t border-gray-100 px-4 py-4 dark:border-slate-700/60 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-gray-500 dark:text-slate-400">{{ __('messages.settings_fields_note') }}</p>
                        <button type="submit" :disabled="submitting"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-violet-600 px-6 py-3 text-sm font-black text-white shadow-sm transition hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 disabled:cursor-not-allowed disabled:opacity-70">
                            <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                            <span x-show="submitting" x-cloak>{{ __('messages.settings_saving') }}</span>
                            <span x-show="!submitting">{{ __('messages.save') }} {{ $sectionTitles[$section] }}</span>
                        </button>
                    </div>
                </div>
            </form>
        @elseif ($section === 'footer')
            @include('admin.settings.sections.footer')
        @else
            @include('admin.settings.sections.delivery')
        @endif
    </div>
</div>
@endsection
