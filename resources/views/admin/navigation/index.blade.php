@extends('layouts.admin.app')

@section('title', __('messages.storefront_navigation') . ' - ' . ($store->setting?->store_name ?? $store->name))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6">
    {{-- Header Banner / Title Row --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl px-2.5 py-1.5 shadow-2xs">
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </div>
            <div>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white leading-tight">
                    {{ __('messages.storefront_navigation') }}
                </h1>
                <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                    {{ __('messages.storefront_navigation_desc') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-end sm:self-auto">
            {{-- Reset Defaults Button (triggers modal) --}}
            <button
                type="button"
                onclick="document.getElementById('reset-defaults-modal').classList.remove('hidden')"
                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 text-[11px] font-extrabold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
            >
                <svg class="h-3.5 w-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <span>{{ __('messages.reset_to_defaults') }}</span>
            </button>

            {{-- New Item Button --}}
            <a
                href="{{ route('store.admin.navigation.create', ['store_slug' => $store->slug]) }}"
                class="inline-flex h-7 items-center gap-1 rounded-lg bg-sky-600 px-2.5 text-[11px] font-black text-white hover:bg-sky-700 transition shadow-2xs"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>{{ __('messages.new_navigation_item') }}</span>
            </a>
        </div>
    </div>

    {{-- Centered Row-based Stat Cards (Admin UI/UX Standard v4.1) --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- Total Items --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-none">
                        {{ __('messages.total_items') }}
                    </div>
                    <div class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight mt-0.5">
                        {{ $stats['total'] }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Desktop Active (Max 10) --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" />
                    </svg>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-none">
                        {{ __('messages.desktop_tabs') }}
                    </div>
                    <div class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight mt-0.5">
                        {{ $stats['desktop_count'] }} <span class="text-xs font-semibold text-slate-400">/ 10</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mobile Bottom Bar (Max 5) --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-none">
                        {{ __('messages.mobile_bottom') }}
                    </div>
                    <div class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight mt-0.5">
                        {{ $stats['bottom_count'] }} <span class="text-xs font-semibold text-slate-400">/ 5</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mobile Drawer Items --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5m-16.5 4.5h16.5" />
                    </svg>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-none">
                        {{ __('messages.mobile_drawer') }}
                    </div>
                    <div class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight mt-0.5">
                        {{ $stats['drawer_count'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-1.5 shadow-2xs">
        {{-- Search Input --}}
        <form method="GET" action="{{ route('store.admin.navigation.index', ['store_slug' => $store->slug]) }}" class="flex items-center gap-1 flex-1 max-w-md">
            <input type="hidden" name="placement" value="{{ $placement }}">
            <div class="relative w-full">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="{{ __('messages.search_navigation') }}..."
                    class="h-7 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 pl-7 pr-2 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:border-sky-500 focus:outline-hidden"
                />
                <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
            @if ($search)
                <a href="{{ route('store.admin.navigation.index', ['store_slug' => $store->slug, 'placement' => $placement]) }}" class="inline-flex h-7 items-center px-2 text-[11px] font-bold text-slate-500 hover:text-slate-800">
                    {{ __('messages.clear') }}
                </a>
            @endif
        </form>

        {{-- Filter Pills & Export Buttons --}}
        <div class="flex items-center gap-1 flex-wrap">
            {{-- Placement Pills --}}
            <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 p-0.5 text-[11px] font-extrabold">
                <a href="{{ route('store.admin.navigation.index', ['store_slug' => $store->slug, 'placement' => 'all', 'search' => $search]) }}"
                   class="rounded-md px-2 py-0.5 transition {{ $placement === 'all' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                    {{ __('messages.all') }}
                </a>
                <a href="{{ route('store.admin.navigation.index', ['store_slug' => $store->slug, 'placement' => 'desktop', 'search' => $search]) }}"
                   class="rounded-md px-2 py-0.5 transition {{ $placement === 'desktop' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                    {{ __('messages.desktop') }}
                </a>
                <a href="{{ route('store.admin.navigation.index', ['store_slug' => $store->slug, 'placement' => 'mobile_bottom', 'search' => $search]) }}"
                   class="rounded-md px-2 py-0.5 transition {{ $placement === 'mobile_bottom' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                    {{ __('messages.bottom_bar') }}
                </a>
                <a href="{{ route('store.admin.navigation.index', ['store_slug' => $store->slug, 'placement' => 'mobile_drawer', 'search' => $search]) }}"
                   class="rounded-md px-2 py-0.5 transition {{ $placement === 'mobile_drawer' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                    {{ __('messages.drawer') }}
                </a>
            </div>

            {{-- Export Buttons --}}
            <a
                href="{{ route('store.admin.navigation.export', ['store_slug' => $store->slug, 'format' => 'xlsx']) }}"
                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 text-[11px] font-extrabold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                title="Export Excel"
            >
                <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Excel</span>
            </a>
            <a
                href="{{ route('store.admin.navigation.export', ['store_slug' => $store->slug, 'format' => 'csv']) }}"
                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 text-[11px] font-extrabold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                title="Export CSV"
            >
                <span>CSV</span>
            </a>
        </div>
    </div>

    {{-- Navigation Items Table --}}
    <div class="overflow-x-auto bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/50 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <th class="py-2 px-2.5 w-12 text-center">{{ __('messages.order') }}</th>
                    <th class="py-2 px-2.5">{{ __('messages.label') }} & {{ __('messages.icon') }}</th>
                    <th class="py-2 px-2.5">{{ __('messages.destination') }}</th>
                    <th class="py-2 px-2.5 text-center">{{ __('messages.placements') }}</th>
                    <th class="py-2 px-2.5 text-center">{{ __('messages.auth_required') }}</th>
                    <th class="py-2 px-2.5 text-center">{{ __('messages.status') }}</th>
                    <th class="py-2 px-2.5 text-right w-24">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($items as $item)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        {{-- Reorder Controls --}}
                        <td class="py-1.5 px-2 text-center">
                            <div class="inline-flex flex-col items-center justify-center gap-0.5">
                                <form method="POST" action="{{ route('store.admin.navigation.reorder', ['store_slug' => $store->slug, 'id' => $item->id, 'direction' => 'up']) }}">
                                    @csrf
                                    <button type="submit" class="p-0.5 text-slate-400 hover:text-sky-600 transition" title="Move Up">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                        </svg>
                                    </button>
                                </form>
                                <span class="text-[10px] font-black text-slate-400">{{ $item->sort_order }}</span>
                                <form method="POST" action="{{ route('store.admin.navigation.reorder', ['store_slug' => $store->slug, 'id' => $item->id, 'direction' => 'down']) }}">
                                    @csrf
                                    <button type="submit" class="p-0.5 text-slate-400 hover:text-sky-600 transition" title="Move Down">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>

                        {{-- Label & Icon --}}
                        <td class="py-1.5 px-2.5">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    <x-storefront.navigation-icon :name="$item->icon_key" class="h-4 w-4" />
                                </span>
                                <div>
                                    <div class="font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5">
                                        <span>{{ $item->label_en }}</span>
                                        <span class="text-[10px] font-bold text-slate-400">/ {{ $item->label_my }}</span>
                                    </div>
                                    <div class="text-[10px] font-mono text-slate-400">
                                        {{ $item->menu_key }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Destination Details --}}
                        <td class="py-1.5 px-2.5">
                            @if ($item->destination_type === 'system')
                                <span class="inline-flex items-center gap-1 rounded-md bg-sky-50 dark:bg-sky-950/50 px-1.5 py-0.5 text-[10px] font-extrabold text-sky-700 dark:text-sky-300 border border-sky-200/60 dark:border-sky-800">
                                    <span>⚙️ {{ __('messages.system') }}:</span>
                                    <span class="font-mono">{{ $item->destination_key }}</span>
                                </span>
                            @elseif ($item->destination_type === 'page')
                                <span class="inline-flex items-center gap-1 rounded-md bg-purple-50 dark:bg-purple-950/50 px-1.5 py-0.5 text-[10px] font-extrabold text-purple-700 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800">
                                    <span>📄 {{ __('messages.page') }}:</span>
                                    <span>{{ $item->storefrontPage?->title_en ?: ('ID #' . $item->storefront_page_id) }}</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 dark:bg-amber-950/50 px-1.5 py-0.5 text-[10px] font-extrabold text-amber-700 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800 max-w-[200px] truncate" title="{{ $item->custom_url }}">
                                    <span>🔗 {{ __('messages.custom_url') }}:</span>
                                    <span class="truncate font-mono">{{ $item->custom_url }}</span>
                                </span>
                            @endif
                        </td>

                        {{-- Placements --}}
                        <td class="py-1.5 px-2.5 text-center">
                            <div class="inline-flex items-center gap-1">
                                <span title="{{ __('messages.desktop') }}" class="px-1.5 py-0.5 rounded text-[10px] font-black {{ $item->show_desktop ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 opacity-40' }}">
                                    D
                                </span>
                                <span title="{{ __('messages.mobile_drawer') }}" class="px-1.5 py-0.5 rounded text-[10px] font-black {{ $item->show_mobile_drawer ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 opacity-40' }}">
                                    M
                                </span>
                                <span title="{{ __('messages.mobile_bottom') }}" class="px-1.5 py-0.5 rounded text-[10px] font-black {{ $item->show_mobile_bottom ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 opacity-40' }}">
                                    B
                                </span>
                            </div>
                        </td>

                        {{-- Requires Auth --}}
                        <td class="py-1.5 px-2.5 text-center">
                            @if ($item->requires_auth)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                                    🔒 {{ __('messages.auth_only') }}
                                </span>
                            @else
                                <span class="text-[10px] font-semibold text-slate-400">
                                    {{ __('messages.public') }}
                                </span>
                            @endif
                        </td>

                        {{-- Enabled / Disabled Status Toggle --}}
                        <td class="py-1.5 px-2.5 text-center">
                            <form method="POST" action="{{ route('store.admin.navigation.toggle', ['store_slug' => $store->slug, 'id' => $item->id]) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black transition {{ $item->is_enabled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 hover:bg-slate-200' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $item->is_enabled ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    <span>{{ $item->is_enabled ? __('messages.active') : __('messages.disabled') }}</span>
                                </button>
                            </form>
                        </td>

                        {{-- Actions --}}
                        <td class="py-1.5 px-2.5 text-right">
                            <div class="inline-flex items-center gap-1">
                                <a
                                    href="{{ route('store.admin.navigation.edit', ['store_slug' => $store->slug, 'id' => $item->id]) }}"
                                    class="p-1 rounded-md text-slate-500 hover:text-sky-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                    title="{{ __('messages.edit') }}"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </a>

                                <form method="POST" action="{{ route('store.admin.navigation.destroy', ['store_slug' => $store->slug, 'id' => $item->id]) }}" onsubmit="return confirm('{{ __('messages.confirm_delete_navigation_item') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="{{ __('messages.delete') }}">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500 dark:text-slate-400">
                            <div class="flex flex-col items-center justify-center gap-1.5">
                                <svg class="h-8 w-8 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                                <span class="font-bold text-xs">{{ __('messages.no_navigation_items_found') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Reset to Defaults Confirmation Modal --}}
<div id="reset-defaults-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 hidden">
    <div class="w-full max-w-md rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-5 shadow-xl animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white">
                    {{ __('messages.reset_to_defaults') }}?
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                    {{ __('messages.reset_to_defaults_warning') }}
                </p>
            </div>
        </div>

        <div class="mt-5 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800 pt-3">
            <button
                type="button"
                onclick="document.getElementById('reset-defaults-modal').classList.add('hidden')"
                class="h-7 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50"
            >
                {{ __('messages.cancel') }}
            </button>
            <form method="POST" action="{{ route('store.admin.navigation.reset_defaults', ['store_slug' => $store->slug]) }}">
                @csrf
                <button
                    type="submit"
                    class="h-7 px-3 rounded-lg bg-amber-600 text-xs font-black text-white hover:bg-amber-700 transition"
                >
                    {{ __('messages.confirm_reset') }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
