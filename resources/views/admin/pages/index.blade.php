@extends('layouts.admin.app')

@section('title', __('messages.custom_pages') . ' - ' . ($store->setting?->store_name ?? $store->name))
@section('main_padding', 'p-0.5 sm:p-1')

@section('content')
<div class="w-full space-y-0.5 pb-6">
    {{-- Header Banner / Title Row --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl px-2.5 py-1.5 shadow-2xs">
        <div class="flex items-center gap-2">
            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xs sm:text-sm font-black text-slate-900 dark:text-white leading-tight">
                    {{ __('messages.custom_pages') }}
                </h1>
                <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                    {{ __('messages.custom_pages_desc') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-1.5 self-end sm:self-auto">
            <a
                href="{{ route('store.admin.pages.create', ['store_slug' => $store->slug]) }}"
                class="inline-flex h-7 items-center gap-1 rounded-lg bg-sky-600 px-2.5 text-[11px] font-black text-white hover:bg-sky-700 transition shadow-2xs"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>{{ __('messages.new_custom_page') }}</span>
            </a>
        </div>
    </div>

    {{-- Centered Row-based Stat Cards (Admin UI/UX Standard v4.1) --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-0.5 sm:gap-1">
        {{-- Total Pages --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-none">
                        {{ __('messages.total_pages') }}
                    </div>
                    <div class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight mt-0.5">
                        {{ $stats['total'] }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Published Pages --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-none">
                        {{ __('messages.published') }}
                    </div>
                    <div class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight mt-0.5">
                        {{ $stats['published_count'] }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Draft Pages --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                    </svg>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-none">
                        {{ __('messages.draft') }}
                    </div>
                    <div class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight mt-0.5">
                        {{ $stats['draft_count'] }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Linked to Navigation --}}
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-2 shadow-2xs">
            <div class="flex items-center justify-center gap-2.5 sm:gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                    </svg>
                </div>
                <div class="text-center">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 leading-none">
                        {{ __('messages.linked_in_menu') }}
                    </div>
                    <div class="text-base sm:text-lg font-black text-slate-900 dark:text-white leading-tight mt-0.5">
                        {{ $stats['linked_count'] }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl p-1.5 shadow-2xs">
        {{-- Search Input --}}
        <form method="GET" action="{{ route('store.admin.pages.index', ['store_slug' => $store->slug]) }}" class="flex items-center gap-1 flex-1 max-w-md">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="relative w-full">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="{{ __('messages.search_pages') }}..."
                    class="h-7 w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 pl-7 pr-2 text-xs font-semibold text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:border-sky-500 focus:outline-hidden"
                />
                <svg class="absolute left-2 top-2 h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
            </div>
            @if ($search)
                <a href="{{ route('store.admin.pages.index', ['store_slug' => $store->slug, 'status' => $status]) }}" class="inline-flex h-7 items-center px-2 text-[11px] font-bold text-slate-500 hover:text-slate-800">
                    {{ __('messages.clear') }}
                </a>
            @endif
        </form>

        {{-- Filter Pills & Export Buttons --}}
        <div class="flex items-center gap-1 flex-wrap">
            {{-- Status Pills --}}
            <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 p-0.5 text-[11px] font-extrabold">
                <a href="{{ route('store.admin.pages.index', ['store_slug' => $store->slug, 'status' => 'all', 'search' => $search]) }}"
                   class="rounded-md px-2 py-0.5 transition {{ $status === 'all' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                    {{ __('messages.all') }}
                </a>
                <a href="{{ route('store.admin.pages.index', ['store_slug' => $store->slug, 'status' => 'published', 'search' => $search]) }}"
                   class="rounded-md px-2 py-0.5 transition {{ $status === 'published' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                    {{ __('messages.published') }}
                </a>
                <a href="{{ route('store.admin.pages.index', ['store_slug' => $store->slug, 'status' => 'draft', 'search' => $search]) }}"
                   class="rounded-md px-2 py-0.5 transition {{ $status === 'draft' ? 'bg-white dark:bg-slate-700 text-sky-600 dark:text-sky-400 shadow-2xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                    {{ __('messages.draft') }}
                </a>
            </div>

            {{-- Export Buttons --}}
            <a
                href="{{ route('store.admin.pages.export', ['store_slug' => $store->slug, 'format' => 'xlsx']) }}"
                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 text-[11px] font-extrabold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                title="Export Excel"
            >
                <svg class="h-3.5 w-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Excel</span>
            </a>
            <a
                href="{{ route('store.admin.pages.export', ['store_slug' => $store->slug, 'format' => 'csv']) }}"
                class="inline-flex h-7 items-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 text-[11px] font-extrabold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                title="Export CSV"
            >
                <span>CSV</span>
            </a>
        </div>
    </div>

    {{-- Custom Pages Table --}}
    <div class="overflow-x-auto bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 rounded-xl shadow-2xs">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/50 text-[10px] font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                    <th class="py-2 px-2.5 w-12 text-center">#</th>
                    <th class="py-2 px-2.5">{{ __('messages.page_title') }}</th>
                    <th class="py-2 px-2.5">{{ __('messages.slug_url') }}</th>
                    <th class="py-2 px-2.5 text-center">{{ __('messages.status') }}</th>
                    <th class="py-2 px-2.5 text-center">{{ __('messages.published_at') }}</th>
                    <th class="py-2 px-2.5 text-center">{{ __('messages.linked_in_menu') }}</th>
                    <th class="py-2 px-2.5 text-right w-28">{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($pages as $page)
                    <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                        {{-- Image / Index --}}
                        <td class="py-1.5 px-2 text-center">
                            @if ($page->featured_image_path)
                                <img
                                    src="{{ asset('storage/' . $page->featured_image_path) }}"
                                    alt="{{ $page->title_en }}"
                                    class="h-7 w-7 rounded-lg object-cover mx-auto border border-slate-200 dark:border-slate-700"
                                />
                            @else
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400 font-mono text-[10px] mx-auto">
                                    📄
                                </span>
                            @endif
                        </td>

                        {{-- Title --}}
                        <td class="py-1.5 px-2.5">
                            <div>
                                <div class="font-extrabold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <span>{{ $page->title_en }}</span>
                                    <span class="text-[10px] font-bold text-slate-400">/ {{ $page->title_my }}</span>
                                </div>
                                @if ($page->summary_en || $page->summary_my)
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-sm">
                                        {{ $page->summary_en ?: $page->summary_my }}
                                    </div>
                                @endif
                            </div>
                        </td>

                        {{-- Slug & Preview Link --}}
                        <td class="py-1.5 px-2.5">
                            <div class="flex items-center gap-1.5 font-mono text-[11px] text-sky-600 dark:text-sky-400">
                                <span>/page/{{ $page->slug }}</span>
                                @if ($page->isPublished())
                                    <a
                                        href="{{ url('/store/' . $store->slug . '/page/' . $page->slug) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="p-0.5 rounded text-slate-400 hover:text-sky-600 transition"
                                        title="{{ __('messages.view_page_on_storefront') }}"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </td>

                        {{-- Status Toggle --}}
                        <td class="py-1.5 px-2.5 text-center">
                            <form method="POST" action="{{ route('store.admin.pages.toggle', ['store_slug' => $store->slug, 'id' => $page->id]) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black transition {{ $page->isPublished() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 hover:bg-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 hover:bg-amber-200' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $page->isPublished() ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                    <span>{{ ucfirst($page->status) }}</span>
                                </button>
                            </form>
                        </td>

                        {{-- Published At --}}
                        <td class="py-1.5 px-2.5 text-center text-slate-500 dark:text-slate-400 text-[11px]">
                            {{ $page->published_at ? $page->published_at->format('M d, Y') : '-' }}
                        </td>

                        {{-- Navigation Link Count --}}
                        <td class="py-1.5 px-2.5 text-center">
                            @if ($page->navigation_items_count > 0)
                                <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 dark:bg-indigo-950/50 px-2 py-0.5 text-[10px] font-black text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800">
                                    🔗 {{ $page->navigation_items_count }} {{ __('messages.menu_items') }}
                                </span>
                            @else
                                <span class="text-[10px] font-semibold text-slate-400">
                                    0
                                </span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="py-1.5 px-2.5 text-right">
                            <div class="inline-flex items-center gap-1">
                                <a
                                    href="{{ route('store.admin.pages.edit', ['store_slug' => $store->slug, 'id' => $page->id]) }}"
                                    class="p-1 rounded-md text-slate-500 hover:text-sky-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                    title="{{ __('messages.edit') }}"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </a>

                                <form method="POST" action="{{ route('store.admin.pages.destroy', ['store_slug' => $store->slug, 'id' => $page->id]) }}" onsubmit="return confirm('{{ __('messages.confirm_delete_page') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="p-1 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                        title="{{ __('messages.delete') }}"
                                    >
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
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <span class="font-bold text-xs">{{ __('messages.no_custom_pages_found') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
