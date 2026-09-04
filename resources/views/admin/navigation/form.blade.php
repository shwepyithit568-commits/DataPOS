@extends('layouts.admin.app')

@php
    $isEdit = $item->exists;
    $title = $isEdit ? __('messages.edit_navigation_item') : __('messages.new_navigation_item');
    $actionUrl = $isEdit
        ? route('admin.navigation.update', ['store_slug' => $store->slug, 'id' => $item->id])
        : route('admin.navigation.store', ['store_slug' => $store->slug]);
@endphp

@section('title', $title . ' - ' . ($store->setting?->store_name ?? $store->name))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full max-w-4xl mx-auto space-y-1 pb-8">
    {{-- Top Header Row --}}
    <div class="flex items-center justify-between bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl px-3 py-2 shadow-2xs">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.navigation.index', ['store_slug' => $store->slug]) }}" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white leading-tight">
                    {{ $title }}
                </h1>
                <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                    {{ __('messages.configure_storefront_menu_item') }}
                </p>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50/80 p-3 text-xs text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200">
            <div class="font-bold flex items-center gap-1.5 mb-1">
                <svg class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                <span>{{ __('messages.please_fix_errors_below') }}</span>
            </div>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main Form Card --}}
    <form method="POST" action="{{ $actionUrl }}" class="space-y-1.5" x-data="{
        destType: '{{ old('destination_type', $item->destination_type ?? 'system') }}',
        selectedIcon: '{{ old('icon_key', $item->icon_key ?? 'home') }}'
    }">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        {{-- 1. Tri-Lingual Labels & Key --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 shadow-2xs space-y-2.5">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 pb-1.5 flex items-center gap-1.5">
                <span>🌐</span>
                <span>{{ __('messages.labels_and_identification') }}</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.label_my') }} <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="label_my"
                        value="{{ old('label_my', $item->label_my) }}"
                        required
                        placeholder="ဥပမာ- ကုန်ပစ္စည်းများ"
                        class="h-8 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2.5 text-xs font-semibold text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    />
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.label_en') }} <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="label_en"
                        value="{{ old('label_en', $item->label_en) }}"
                        required
                        placeholder="e.g. Products"
                        class="h-8 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2.5 text-xs font-semibold text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    />
                </div>

                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.label_zh_cn') }}
                    </label>
                    <input
                        type="text"
                        name="label_zh_cn"
                        value="{{ old('label_zh_cn', $item->label_zh_cn) }}"
                        placeholder="例如- 商品"
                        class="h-8 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2.5 text-xs font-semibold text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    />
                </div>
            </div>

            @if ($isEdit)
                <div>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.menu_key') }}
                    </label>
                    <input
                        type="text"
                        name="menu_key"
                        value="{{ old('menu_key', $item->menu_key) }}"
                        class="h-8 w-full max-w-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800/80 px-2.5 text-xs font-mono text-slate-600 dark:text-slate-400"
                    />
                </div>
            @endif
        </div>

        {{-- 2. Destination Type & Target --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 shadow-2xs space-y-2.5">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 pb-1.5 flex items-center gap-1.5">
                <span>🎯</span>
                <span>{{ __('messages.destination_target') }}</span>
            </h2>

            {{-- Type Switcher Pills --}}
            <div class="flex items-center gap-2">
                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-slate-800 dark:text-slate-200">
                    <input type="radio" name="destination_type" value="system" x-model="destType" class="text-sky-600 focus:ring-sky-500">
                    <span>{{ __('messages.system_page') }}</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-slate-800 dark:text-slate-200 ml-4">
                    <input type="radio" name="destination_type" value="page" x-model="destType" class="text-sky-600 focus:ring-sky-500">
                    <span>{{ __('messages.custom_page') }}</span>
                </label>
                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-bold text-slate-800 dark:text-slate-200 ml-4">
                    <input type="radio" name="destination_type" value="custom_url" x-model="destType" class="text-sky-600 focus:ring-sky-500">
                    <span>{{ __('messages.custom_url') }}</span>
                </label>
            </div>

            {{-- Target Input according to type --}}
            <div class="pt-1">
                {{-- System Dropdown --}}
                <div x-show="destType === 'system'" x-cloak>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.select_system_destination') }} <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="destination_key"
                        class="h-8 w-full max-w-md rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2.5 text-xs font-semibold text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    >
                        @foreach ($systemDestinations as $key => $sys)
                            <option value="{{ $key }}" @selected(old('destination_key', $item->destination_key) === $key)>
                                {{ __($sys['label_key']) }} ({{ $key }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Custom Page Dropdown --}}
                <div x-show="destType === 'page'" x-cloak>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.select_custom_page') }} <span class="text-rose-500">*</span>
                    </label>
                    @if ($pages->isEmpty())
                        <div class="rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 p-2 text-xs text-amber-800 dark:text-amber-300">
                            {{ __('messages.no_published_pages_found') }}
                            <a href="{{ route('admin.pages.create', ['store_slug' => $store->slug]) }}" class="font-bold underline ml-1">
                                {{ __('messages.create_page_now') }}
                            </a>
                        </div>
                    @else
                        <select
                            name="storefront_page_id"
                            class="h-8 w-full max-w-md rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2.5 text-xs font-semibold text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                        >
                            <option value="">-- {{ __('messages.choose_page') }} --</option>
                            @foreach ($pages as $p)
                                <option value="{{ $p->id }}" @selected((int) old('storefront_page_id', $item->storefront_page_id) === $p->id)>
                                    {{ $p->title_en }} ({{ $p->slug }})
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                {{-- Custom URL Input --}}
                <div x-show="destType === 'custom_url'" x-cloak>
                    <label class="block text-[11px] font-black text-slate-700 dark:text-slate-300 mb-1">
                        {{ __('messages.enter_custom_url') }} <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="custom_url"
                        value="{{ old('custom_url', $item->custom_url) }}"
                        placeholder="https://example.com/page or /custom-path"
                        class="h-8 w-full max-w-lg rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 px-2.5 text-xs font-semibold text-slate-900 dark:text-white focus:border-sky-500 focus:outline-hidden"
                    />
                    <p class="text-[10px] text-slate-400 mt-1">
                        {{ __('messages.custom_url_helper_note') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- 3. Icon Selection --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 shadow-2xs space-y-2.5">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 pb-1.5 flex items-center gap-1.5">
                <span>🎨</span>
                <span>{{ __('messages.menu_icon') }}</span>
            </h2>

            <div class="grid grid-cols-4 sm:grid-cols-7 gap-1.5">
                @foreach ($iconKeys as $k)
                    <label
                        @click="selectedIcon = '{{ $k }}'"
                        class="flex flex-col items-center justify-center p-2 rounded-xl border cursor-pointer transition text-center"
                        :class="selectedIcon === '{{ $k }}' ? 'border-sky-500 bg-sky-50/75 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 ring-2 ring-sky-500/20 font-black' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 text-slate-600 dark:text-slate-400 hover:border-slate-300 font-bold'"
                    >
                        <input type="radio" name="icon_key" value="{{ $k }}" x-model="selectedIcon" class="sr-only">
                        <x-storefront.navigation-icon :name="$k" class="h-5 w-5 mb-1" />
                        <span class="text-[10px] truncate max-w-full">{{ $k }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- 4. Placements & Permissions --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-3 shadow-2xs space-y-2.5">
            <h2 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400 border-b border-slate-100 dark:border-slate-800 pb-1.5 flex items-center gap-1.5">
                <span>📱</span>
                <span>{{ __('messages.placements_and_access') }}</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {{-- Show Desktop --}}
                <label class="flex items-start gap-2 cursor-pointer p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                    <input type="hidden" name="show_desktop" value="0">
                    <input
                        type="checkbox"
                        name="show_desktop"
                        value="1"
                        @checked(old('show_desktop', $item->show_desktop))
                        class="mt-0.5 rounded text-sky-600 focus:ring-sky-500"
                    />
                    <div>
                        <div class="text-xs font-black text-slate-900 dark:text-white">
                            {{ __('messages.desktop_tab_bar') }}
                        </div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400">
                            {{ __('messages.max_10_desktop_note') }}
                        </div>
                    </div>
                </label>

                {{-- Show Mobile Bottom --}}
                <label class="flex items-start gap-2 cursor-pointer p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                    <input type="hidden" name="show_mobile_bottom" value="0">
                    <input
                        type="checkbox"
                        name="show_mobile_bottom"
                        value="1"
                        @checked(old('show_mobile_bottom', $item->show_mobile_bottom))
                        class="mt-0.5 rounded text-sky-600 focus:ring-sky-500"
                    />
                    <div>
                        <div class="text-xs font-black text-slate-900 dark:text-white">
                            {{ __('messages.mobile_bottom_bar') }}
                        </div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400">
                            {{ __('messages.max_5_bottom_note') }}
                        </div>
                    </div>
                </label>

                {{-- Show Mobile Drawer --}}
                <label class="flex items-start gap-2 cursor-pointer p-2 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40">
                    <input type="hidden" name="show_mobile_drawer" value="0">
                    <input
                        type="checkbox"
                        name="show_mobile_drawer"
                        value="1"
                        @checked(old('show_mobile_drawer', $item->show_mobile_drawer))
                        class="mt-0.5 rounded text-sky-600 focus:ring-sky-500"
                    />
                    <div>
                        <div class="text-xs font-black text-slate-900 dark:text-white">
                            {{ __('messages.mobile_drawer_menu') }}
                        </div>
                        <div class="text-[10px] text-slate-500 dark:text-slate-400">
                            {{ __('messages.shown_in_slide_drawer') }}
                        </div>
                    </div>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 border-t border-slate-100 dark:border-slate-800">
                {{-- Requires Auth --}}
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="hidden" name="requires_auth" value="0">
                    <input
                        type="checkbox"
                        name="requires_auth"
                        value="1"
                        @checked(old('requires_auth', $item->requires_auth))
                        class="mt-0.5 rounded text-sky-600 focus:ring-sky-500"
                    />
                    <div>
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            {{ __('messages.requires_customer_login') }}
                        </div>
                        <div class="text-[10px] text-slate-400">
                            {{ __('messages.requires_customer_login_desc') }}
                        </div>
                    </div>
                </label>

                {{-- Is Enabled --}}
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="hidden" name="is_enabled" value="0">
                    <input
                        type="checkbox"
                        name="is_enabled"
                        value="1"
                        @checked(old('is_enabled', $item->is_enabled ?? true))
                        class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-500"
                    />
                    <div>
                        <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                            {{ __('messages.is_enabled') }}
                        </div>
                        <div class="text-[10px] text-slate-400">
                            {{ __('messages.is_enabled_desc') }}
                        </div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Form Actions Bar --}}
        <div class="flex items-center justify-end gap-2 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2.5 shadow-2xs">
            <a
                href="{{ route('admin.navigation.index', ['store_slug' => $store->slug]) }}"
                class="inline-flex h-8 items-center px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition"
            >
                {{ __('messages.cancel') }}
            </a>
            <button
                type="submit"
                class="inline-flex h-8 items-center gap-1.5 px-4 rounded-lg bg-sky-600 text-xs font-black text-white hover:bg-sky-700 transition shadow-2xs"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <span>{{ __('messages.save') }}</span>
            </button>
        </div>
    </form>
</div>
@endsection
