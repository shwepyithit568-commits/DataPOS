@extends('layouts.admin.app')

@section('title', __('messages.banners_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $baseParams = $storeRouteParams;
    $currentSort = request()->only('sort', 'search');
    $clearFiltersUrl = route('store.admin.banners.index', $baseParams);

    // Accent color tokens for KPI stat cards
    $statAccents = [
        'total'   => 'bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300',
        'active'  => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300',
        'hidden'  => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        'preview' => 'bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300',
    ];

    $statBorders = [
        'total'   => 'hover:border-violet-300 dark:hover:border-violet-700/80',
        'active'  => 'hover:border-emerald-300 dark:hover:border-emerald-700/80',
        'hidden'  => 'hover:border-slate-300 dark:hover:border-slate-700/80',
        'preview' => 'hover:border-sky-300 dark:hover:border-sky-700/80',
    ];
@endphp

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
        viewMode: localStorage.getItem('admin_banners_view_mode') || 'grid',
        showCreateModal: false
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_banners_view_mode', $event.detail)">

    {{-- ============================================================
         PAGE HEADER — eyebrow badge, title, subtitle, CTA row
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-violet-100 dark:border-violet-900/60 mb-0.5">
                <span>🖼️</span>
                <span>{{ __('messages.sidebar_banners') }}</span>
                <span class="text-slate-400 dark:text-slate-500">·</span>
                <span class="font-normal normal-case text-slate-500 dark:text-slate-400">Storefront Hero Slider</span>
            </div>
            <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                {{ __('messages.banners_title') }}
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ $store->name }} · {{ __('messages.banners_subtitle') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0">
            <a href="{{ route('storefront.store.home', ['store_slug' => $store->slug]) }}" target="_blank"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800 transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>{{ __('messages.web_catalog_preview_storefront') }}</span>
            </a>

            <button type="button" @click="showCreateModal = true"
                    class="px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.banners_add_new') }}</span>
            </button>
        </div>
    </header>

    {{-- Flash Notifications & Validation Errors --}}
    @if(session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(isset($errors) && $errors->any())
        <div class="w-full p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-2xs">
            @foreach($errors->all() as $e)
                <div class="flex items-center gap-1.5 font-bold"><span>⚠️</span><span>{{ $e }}</span></div>
            @endforeach
        </div>
    @endif

    {{-- ============================================================
         KPI STAT CARDS — 3 responsive interactive cards
         ============================================================ --}}
    <div class="w-full grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-2.5" role="list" aria-label="{{ __('messages.banners_title') }}">
        {{-- Total Banners --}}
        <a href="{{ $clearFiltersUrl }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['total'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['total'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['total']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.banners_total') }}
                </p>
            </div>
        </a>

        {{-- Active Banners --}}
        <a href="{{ route('store.admin.banners.index', array_merge($baseParams, ['status' => 'active'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['active'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['active'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['active']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.banners_active') }}
                </p>
            </div>
        </a>

        {{-- Hidden Banners --}}
        <a href="{{ route('store.admin.banners.index', array_merge($baseParams, ['status' => 'hidden'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['hidden'] }} col-span-2 sm:col-span-1">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['hidden'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-slate-600 dark:text-slate-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['hidden']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.banners_hidden') }}
                </p>
            </div>
        </a>
    </div>

    {{-- ============================================================
         REUSABLE ADMIN TOOLBAR COMPONENT
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', $search ?? '')"
        searchPlaceholder="Search banner title, description or link..."
        :sort="request('sort', $sort ?? 'sort_order')"
        :sortOptions="[
            'sort_order' => 'Sort Order (0, 1, 2…)',
            'newest'     => 'Newest First',
            'oldest'     => 'Oldest First',
            'title_asc'  => 'Title (A to Z)',
        ]"
        :filters="[
            'status' => [
                'label'   => __('messages.status'),
                'options' => [
                    'active' => __('messages.banners_status_active'),
                    'hidden' => __('messages.banners_status_hidden'),
                ]
            ]
        ]"
        :showViewToggle="true"
        :showExportImport="false"
        :totalCount="$banners->count()"
    />

    {{-- ============================================================
         GRID VIEW — Showcase banner cards with 16:9 ratio
         ============================================================ --}}
    <div id="banners-grid" x-show="viewMode === 'grid'" class="w-full">
        @if($banners->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($banners as $banner)
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-2xs hover:shadow-sm transition flex flex-col justify-between group">
                        <div>
                            {{-- Banner Image Preview --}}
                            <div class="relative bg-slate-100 dark:bg-slate-800 overflow-hidden aspect-[16/8] sm:aspect-[16/7.5]">
                                <img src="{{ asset('storage/' . $banner->image_path) }}" alt="{{ $banner->title }}"
                                     class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                
                                {{-- Status badge --}}
                                <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider backdrop-blur-xs shadow-2xs {{ $banner->is_active ? 'bg-emerald-600/90 text-white' : 'bg-slate-700/90 text-white' }}">
                                    {{ $banner->is_active ? '● ' . __('messages.banners_status_active') : '○ ' . __('messages.banners_status_hidden') }}
                                </span>

                                {{-- Sort Order badge --}}
                                <span class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-slate-950/70 text-white text-[10px] font-black font-mono backdrop-blur-xs">
                                    #{{ (int) $banner->sort_order }}
                                </span>
                            </div>

                            {{-- Card Body --}}
                            <div class="p-3 sm:p-3.5 space-y-1.5">
                                <h3 class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm truncate" title="{{ $banner->title }}">
                                    {{ $banner->title }}
                                </h3>

                                @if ($banner->description)
                                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
                                        {{ $banner->description }}
                                    </p>
                                @endif

                                @if ($banner->link_url)
                                    <a href="{{ $banner->link_url }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-1 text-xs text-sky-600 dark:text-sky-400 hover:underline truncate max-w-full font-mono mt-1" title="{{ $banner->link_url }}">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656M10.5 6H17a4 4 0 014 4v7a4 4 0 01-4 4H7a4 4 0 01-4-4v-3"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l8-8m0 0h-5m5 0v5"/></svg>
                                        <span class="truncate">{{ $banner->link_url }}</span>
                                    </a>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs text-slate-400 mt-1">
                                        <span>— No link attached</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Footer Actions --}}
                        <div class="px-3 sm:px-3.5 py-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2">
                            <a href="{{ route('store.admin.banners.edit', ['store_slug' => $store->slug, 'banner' => $banner->id]) }}"
                               class="flex-1 text-center py-1.5 px-3 rounded-lg bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 hover:bg-violet-100 dark:hover:bg-violet-900/60 font-bold text-xs border border-violet-200 dark:border-violet-800 transition flex items-center justify-center gap-1 active:scale-95">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                <span>{{ __('messages.edit') }}</span>
                            </a>
                            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/banners/' . $banner->id) }}" class="flex-1"
                                  onsubmit="return confirm('{{ __('messages.banners_delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full py-1.5 px-3 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 font-bold text-xs border border-rose-200 dark:border-rose-800 transition flex items-center justify-center gap-1 active:scale-95">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <span>{{ __('messages.delete') }}</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg p-12 text-center shadow-2xs">
                <div class="max-w-sm mx-auto space-y-2.5">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                        <span class="text-xl">🖼️</span>
                    </div>
                    <p class="font-bold text-xs text-slate-700 dark:text-slate-300">{{ __('messages.banners_empty') }}</p>
                    <p class="text-[11px] text-slate-400">{{ __('messages.banners_empty_desc') }}</p>
                    <button type="button" @click="showCreateModal = true" class="inline-block text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                        + {{ __('messages.banners_add_new') }}
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- ============================================================
         TABLE VIEW — spreadsheet grid
         ============================================================ --}}
    <div id="banners-table" x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-2.5 px-3 min-w-[260px]">{{ __('messages.banners_title_label') }}</th>
                        <th class="py-2.5 px-3 min-w-[200px]">{{ __('messages.banners_link_label') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[100px]">{{ __('messages.banners_sort_label') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[110px]">{{ __('messages.status') }}</th>
                        <th class="py-2.5 pl-3 pr-4 text-right w-24">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($banners as $banner)
                        <tr class="hover:bg-violet-50/50 dark:hover:bg-violet-950/20 divide-x divide-slate-200/80 dark:divide-slate-800 transition-colors group">
                            {{-- Banner Preview & Title --}}
                            <td class="py-2.5 px-3">
                                <div class="flex items-center gap-2.5">
                                    <img src="{{ asset('storage/' . $banner->image_path) }}" alt="{{ $banner->title }}"
                                         class="w-14 h-8 rounded object-cover border border-slate-200/80 dark:border-slate-700 bg-slate-100 shrink-0">
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 text-xs truncate max-w-xs">
                                            {{ $banner->title }}
                                        </div>
                                        @if($banner->description)
                                            <div class="text-[11px] text-slate-400 truncate max-w-xs">
                                                {{ $banner->description }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Link URL --}}
                            <td class="py-2.5 px-3 font-mono text-[11px]">
                                @if($banner->link_url)
                                    <a href="{{ $banner->link_url }}" target="_blank" class="text-sky-600 dark:text-sky-400 hover:underline truncate block max-w-xs">
                                        {{ $banner->link_url }}
                                    </a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>

                            {{-- Sort order --}}
                            <td class="py-2.5 px-3 text-center font-mono font-bold text-slate-700 dark:text-slate-300">
                                #{{ (int) $banner->sort_order }}
                            </td>

                            {{-- Status badge --}}
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $banner->is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                    {{ $banner->is_active ? __('messages.banners_status_active') : __('messages.banners_status_hidden') }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="py-2.5 pl-3 pr-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('store.admin.banners.edit', ['store_slug' => $store->slug, 'banner' => $banner->id]) }}"
                                       class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition" title="{{ __('messages.edit') }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </a>
                                    <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/banners/' . $banner->id) }}"
                                          onsubmit="return confirm('{{ __('messages.banners_delete_confirm') }}')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="{{ __('messages.delete') }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="max-w-sm mx-auto space-y-2.5">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                                        <span class="text-xl">🖼️</span>
                                    </div>
                                    <p class="font-bold text-xs text-slate-700 dark:text-slate-300">{{ __('messages.banners_empty') }}</p>
                                    <a href="{{ $clearFiltersUrl }}" class="inline-block text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                                        {{ __('messages.clear_all') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
         MODAL: UPLOAD NEW BANNER
         ============================================================ --}}
    <div x-show="showCreateModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="showCreateModal = false"
             class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-lg w-full p-4 sm:p-5 space-y-3.5 my-6">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 grid place-items-center text-xs">🖼️</span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ __('messages.banners_add_new') }}</h3>
                        <p class="text-[11px] text-slate-400">{{ $store->name }}</p>
                    </div>
                </div>
                <button type="button" @click="showCreateModal = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ url('/store/' . $store->slug . '/admin/banners') }}" enctype="multipart/form-data" class="space-y-3 text-xs">
                @csrf
                <input type="hidden" name="page" value="home" />

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.banners_title_label') }} *</label>
                    <input type="text" name="title" required value="{{ old('title') }}" placeholder="e.g. Summer Mega Sale 2026"
                           class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.banners_desc_label') }}</label>
                    <textarea name="description" rows="2" maxlength="500" placeholder="Banner အောက်တွင် ပြသမည့် အကျဉ်းချုပ်စာသား"
                              class="w-full resize-y border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.banners_link_label') }}</label>
                        <input type="text" name="link_url" value="{{ old('link_url') }}" placeholder="https://... သို့မဟုတ် /products?..."
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.banners_sort_label') }}</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                               class="w-full border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 font-mono font-bold bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">{{ __('messages.banners_image_label') }} *</label>
                    <input type="file" name="image" accept="image/png,image/jpeg,image/jpg,image/webp" required
                           class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-violet-50 dark:file:bg-slate-800 file:text-violet-700 dark:file:text-violet-300 hover:file:bg-violet-100 cursor-pointer">
                    <p class="mt-1 text-[11px] text-slate-400">{{ __('messages.banners_recommended_size') }}</p>
                </div>

                <div class="pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0" />
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500">
                        <span class="font-bold text-slate-700 dark:text-slate-300">{{ __('messages.banners_status_active') }}</span>
                    </label>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showCreateModal = false" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition active:scale-95">
                        {{ __('messages.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-1.5 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition active:scale-95">
                        {{ __('messages.banners_add_new') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
