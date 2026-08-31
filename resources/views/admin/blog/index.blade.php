@extends('layouts.admin.app')

@section('title', __('messages.blog_admin_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $baseParams = $storeRouteParams;
    $currentSort = request()->only('sort', 'search');
    $clearFiltersUrl = route('store.admin.blog.index', $baseParams);

    // Accent color tokens for KPI stat cards
    $statAccents = [
        'total'      => 'bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300',
        'published'  => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300',
        'draft'      => 'bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300',
        'categories' => 'bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300',
    ];

    $statBorders = [
        'total'      => 'hover:border-violet-300 dark:hover:border-violet-700/80',
        'published'  => 'hover:border-emerald-300 dark:hover:border-emerald-700/80',
        'draft'      => 'hover:border-amber-300 dark:hover:border-amber-700/80',
        'categories' => 'hover:border-sky-300 dark:hover:border-sky-700/80',
    ];

    // Build category filter options for toolbar
    $categoryFilterOptions = [];
    foreach ($categories as $cat) {
        $categoryFilterOptions[$cat] = $cat;
    }
@endphp

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
        viewMode: localStorage.getItem('admin_blog_view_mode') || 'table'
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_blog_view_mode', $event.detail)">

    {{-- ============================================================
         PAGE HEADER — eyebrow badge, title, subtitle, CTA row
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                {{ __('messages.blog_admin_title') }}
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ $store->name }} · {{ __('messages.blog_admin_subtitle') }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0">
            <a href="{{ url('/blog?store_slug=' . $store->slug) }}" target="_blank"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800 transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>{{ __('messages.web_catalog_preview_storefront') }}</span>
            </a>

            <a href="{{ route('store.admin.blog.create', ['store_slug' => $store->slug]) }}"
               class="px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                <span>{{ __('messages.blog_add_new') }}</span>
            </a>
        </div>
    </header>

    {{-- Flash Notification --}}
    @if(session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ============================================================
         KPI STAT CARDS — 4 responsive interactive cards
         ============================================================ --}}
    <div class="w-full grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-2.5" role="list" aria-label="{{ __('messages.blog_admin_title') }}">
        {{-- Total Posts --}}
        <a href="{{ $clearFiltersUrl }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['total'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['total'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['total']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.blog_total') }}
                </p>
            </div>
        </a>

        {{-- Published & Live --}}
        <a href="{{ route('store.admin.blog.index', array_merge($baseParams, $currentSort, ['status' => 'published'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['published'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['published'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['published']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.blog_published') }}
                </p>
            </div>
        </a>

        {{-- Drafts --}}
        <a href="{{ route('store.admin.blog.index', array_merge($baseParams, $currentSort, ['status' => 'draft'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['draft'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['draft'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['draft']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.blog_drafts') }}
                </p>
            </div>
        </a>

        {{-- Categories Count --}}
        <div role="listitem"
             class="w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['categories'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-sky-600 dark:text-sky-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['categories_count']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.blog_categories_count') }}
                </p>
            </div>
        </div>
    </div>

    {{-- ============================================================
         REUSABLE ADMIN TOOLBAR COMPONENT
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="__('messages.search_by_name_sku_brand_category')"
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest'    => __('messages.blog_sort_newest'),
            'oldest'    => __('messages.blog_sort_oldest'),
            'title_asc' => __('messages.blog_sort_title'),
        ]"
        :filters="array_filter([
            'status' => [
                'label'   => __('messages.status'),
                'options' => [
                    'published' => __('messages.blog_filter_published'),
                    'draft'     => __('messages.blog_filter_draft'),
                ]
            ],
            'category' => !empty($categoryFilterOptions) ? [
                'label'   => __('messages.categories'),
                'options' => $categoryFilterOptions
            ] : null,
        ])"
        :showViewToggle="true"
        :showExportImport="false"
        :paginator="$posts"
        :perPageOptions="[20 => '20', 50 => '50', 100 => '100', 'all' => __('messages.all')]"
    />

    {{-- ============================================================
         TABLE VIEW — spreadsheet grid with sticky header
         ============================================================ --}}
    <div id="blog-table" x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-2.5 px-3 min-w-[260px]">Article / Post Title</th>
                        <th class="py-2.5 px-3 text-center min-w-[120px]">{{ __('messages.categories') }}</th>
                        <th class="py-2.5 px-3 min-w-[180px]">Storefront Slug / Link</th>
                        <th class="py-2.5 px-3 min-w-[120px]">{{ __('messages.date') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[110px]">{{ __('messages.status') }}</th>
                        <th class="py-2.5 pl-3 pr-4 text-right w-28">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse ($posts as $post)
                        <tr class="hover:bg-violet-50/40 dark:hover:bg-violet-950/20 divide-x divide-slate-200/80 dark:divide-slate-800 transition-colors group">
                            {{-- Article Title & Thumbnail --}}
                            <td class="py-2.5 px-3">
                                <div class="flex items-center gap-2.5">
                                    @if ($post->image_path)
                                        <img src="{{ asset('storage/' . $post->image_path) }}" alt="{{ $post->title }}"
                                             class="w-12 h-9 rounded object-cover border border-slate-200/80 dark:border-slate-700 bg-slate-100 shrink-0">
                                    @else
                                        <div class="w-12 h-9 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 grid place-items-center text-base shrink-0">
                                            📝
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('store.admin.blog.edit', ['store_slug' => $store->slug, 'post' => $post->id]) }}"
                                           class="font-bold text-xs text-slate-900 dark:text-slate-100 hover:text-violet-600 dark:hover:text-violet-400 truncate block max-w-sm"
                                           title="{{ $post->title }}">
                                            {{ $post->title }}
                                        </a>
                                        @if($post->excerpt)
                                            <p class="text-[11px] text-slate-400 truncate max-w-sm mt-0.5">
                                                {{ $post->excerpt }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Category & Tags --}}
                            <td class="py-2.5 px-3 text-center">
                                @if($post->category)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border border-violet-200/60 dark:border-violet-800">
                                        {{ $post->category }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">—</span>
                                @endif
                            </td>

                            {{-- Storefront Link / Slug --}}
                            <td class="py-2.5 px-3 font-mono text-[11px]">
                                <a href="{{ url('/blog/' . $post->slug . '?store_slug=' . $store->slug) }}" target="_blank"
                                   class="text-sky-600 dark:text-sky-400 hover:underline truncate block max-w-xs" title="/blog/{{ $post->slug }}">
                                    /blog/{{ $post->slug }}
                                </a>
                            </td>

                            {{-- Published Date --}}
                            <td class="py-2.5 px-3 text-[11px] font-mono text-slate-500 dark:text-slate-400">
                                <div>{{ $post->published_at?->format('d M Y') ?? $post->created_at->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $post->published_at?->format('h:i A') ?? $post->created_at->format('h:i A') }}</div>
                            </td>

                            {{-- Status --}}
                            <td class="py-2.5 px-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $post->is_published ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                    {{ $post->is_published ? __('messages.blog_filter_published') : __('messages.blog_filter_draft') }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="py-2.5 pl-3 pr-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ url('/blog/' . $post->slug . '?store_slug=' . $store->slug) }}" target="_blank"
                                       class="p-1.5 rounded-lg text-slate-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:bg-sky-950/40 transition" title="Preview on Web">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('store.admin.blog.edit', ['store_slug' => $store->slug, 'post' => $post->id]) }}"
                                       class="p-1.5 rounded-lg text-slate-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition" title="{{ __('messages.edit') }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('store.admin.blog.destroy', ['store_slug' => $store->slug, 'post' => $post->id]) }}"
                                          onsubmit="return confirm('{{ __('messages.blog_delete_confirm') }}')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-rose-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition" title="{{ __('messages.delete') }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="max-w-sm mx-auto space-y-2.5">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                                        <span class="text-xl">📝</span>
                                    </div>
                                    <p class="font-bold text-xs text-slate-700 dark:text-slate-300">{{ __('messages.blog_admin_empty') }}</p>
                                    <p class="text-[11px] text-slate-400">{{ __('messages.blog_empty_desc') }}</p>
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
         CARD / MAGAZINE VIEW — rich grid layout
         ============================================================ --}}
    <div id="blog-cards" x-show="viewMode === 'card'" class="w-full">
        @if($posts->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5">
                @foreach ($posts as $post)
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 overflow-hidden shadow-2xs hover:shadow-sm transition flex flex-col justify-between group">
                        <div>
                            {{-- Featured Image --}}
                            <div class="relative bg-slate-100 dark:bg-slate-800 overflow-hidden aspect-[16/9]">
                                @if ($post->image_path)
                                    <img src="{{ asset('storage/' . $post->image_path) }}" alt="{{ $post->title }}"
                                         class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
                                @else
                                    <div class="w-full h-full grid place-items-center text-3xl opacity-60">
                                        📝
                                    </div>
                                @endif
                                
                                {{-- Status badge --}}
                                <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider backdrop-blur-xs shadow-2xs {{ $post->is_published ? 'bg-emerald-600/90 text-white' : 'bg-slate-700/90 text-white' }}">
                                    {{ $post->is_published ? __('messages.blog_filter_published') : __('messages.blog_filter_draft') }}
                                </span>

                                {{-- Category badge --}}
                                @if($post->category)
                                    <span class="absolute bottom-2 left-2 px-2 py-0.5 rounded-md bg-slate-950/75 text-white text-[10px] font-bold backdrop-blur-xs">
                                        {{ $post->category }}
                                    </span>
                                @endif
                            </div>

                            {{-- Body --}}
                            <div class="p-3 space-y-1.5">
                                <a href="{{ route('store.admin.blog.edit', ['store_slug' => $store->slug, 'post' => $post->id]) }}"
                                   class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100 hover:text-violet-600 dark:hover:text-violet-400 line-clamp-2 leading-tight block">
                                    {{ $post->title }}
                                </a>

                                @if($post->excerpt)
                                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">
                                        {{ $post->excerpt }}
                                    </p>
                                @endif

                                <div class="text-[10px] font-mono text-slate-400 pt-1">
                                    📅 {{ $post->published_at?->format('d M Y') ?? $post->created_at->format('d M Y') }}
                                </div>
                            </div>
                        </div>

                        {{-- Footer Actions --}}
                        <div class="px-3 py-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-1">
                            <a href="{{ url('/blog/' . $post->slug . '?store_slug=' . $store->slug) }}" target="_blank"
                               class="text-xs text-sky-600 dark:text-sky-400 hover:underline font-bold flex items-center gap-1">
                                <span>Preview</span>
                                <span>↗</span>
                            </a>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('store.admin.blog.edit', ['store_slug' => $store->slug, 'post' => $post->id]) }}"
                                   class="p-1.5 rounded-lg text-slate-500 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition" title="{{ __('messages.edit') }}">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('store.admin.blog.destroy', ['store_slug' => $store->slug, 'post' => $post->id]) }}"
                                      onsubmit="return confirm('{{ __('messages.blog_delete_confirm') }}')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 transition" title="{{ __('messages.delete') }}">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg p-12 text-center shadow-2xs">
                <div class="max-w-sm mx-auto space-y-2.5">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                        <span class="text-xl">📝</span>
                    </div>
                    <p class="font-bold text-xs text-slate-700 dark:text-slate-300">{{ __('messages.blog_admin_empty') }}</p>
                    <p class="text-[11px] text-slate-400">{{ __('messages.blog_empty_desc') }}</p>
                    <a href="{{ $clearFiltersUrl }}" class="inline-block text-xs font-bold text-violet-600 dark:text-violet-400 hover:underline">
                        {{ __('messages.clear_all') }}
                    </a>
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
