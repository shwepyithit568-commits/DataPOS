@extends('layouts.admin.app')

@section('title', __('messages.reviews_title') . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@php
    $storeRouteParams = ['store_slug' => $store->slug];
    $baseParams = $storeRouteParams;
    $currentSort = request()->only('sort', 'search');
    $clearFiltersUrl = route('store.admin.reviews.index', $baseParams);

    // Accent color tokens for KPI stat cards
    $statAccents = [
        'total'     => 'bg-violet-100 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300',
        'pending'   => 'bg-amber-100 text-amber-600 dark:bg-amber-950/70 dark:text-amber-300',
        'approved'  => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300',
        'rating'    => 'bg-sky-100 text-sky-600 dark:bg-sky-950/70 dark:text-sky-300',
        'five_star' => 'bg-rose-100 text-rose-600 dark:bg-rose-950/70 dark:text-rose-300',
    ];

    $statBorders = [
        'total'     => 'hover:border-violet-300 dark:hover:border-violet-700/80',
        'pending'   => 'hover:border-amber-300 dark:hover:border-amber-700/80',
        'approved'  => 'hover:border-emerald-300 dark:hover:border-emerald-700/80',
        'rating'    => 'hover:border-sky-300 dark:hover:border-sky-700/80',
        'five_star' => 'hover:border-rose-300 dark:hover:border-rose-700/80',
    ];
@endphp

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="{
        viewMode: localStorage.getItem('admin_reviews_view_mode') || 'table',
        detailsModal: false,
        selectedReview: null,

        openDetails(review) {
            this.selectedReview = review;
            this.detailsModal = true;
        }
     }"
     @view-changed.window="viewMode = $event.detail; localStorage.setItem('admin_reviews_view_mode', $event.detail)">

    {{-- ============================================================
         PAGE HEADER — eyebrow badge, title, subtitle, CTA row
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                {{ __('messages.reviews_title') }}
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ $store->name }} · {{ __('messages.reviews_subtitle') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0">
            @if($stats['pending'] > 0)
                <a href="{{ route('store.admin.reviews.index', array_merge($baseParams, ['status' => 'pending'])) }}"
                   class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-200 dark:border-amber-800 transition flex items-center gap-1.5 shadow-2xs active:scale-95 animate-pulse">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>{{ $stats['pending'] }} {{ __('messages.reviews_filter_pending') }}</span>
                </a>
            @endif
            <a href="{{ route('storefront.store.home', ['store_slug' => $store->slug]) }}" target="_blank"
               class="px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-lg text-xs font-bold bg-sky-600 hover:bg-sky-700 text-white shadow-2xs transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>{{ __('messages.web_catalog_preview_storefront') }}</span>
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
         KPI STAT CARDS — 5 responsive interactive cards
         ============================================================ --}}
    <div class="w-full grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 sm:gap-2.5" role="list" aria-label="{{ __('messages.reviews_title') }}">
        {{-- Total Reviews --}}
        <a href="{{ $clearFiltersUrl }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['total'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['total'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-slate-900 dark:text-slate-100 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['total']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.reviews_total') }}
                </p>
            </div>
        </a>

        {{-- Pending Moderation --}}
        <a href="{{ route('store.admin.reviews.index', array_merge($baseParams, $currentSort, ['status' => 'pending'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['pending'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['pending'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-amber-600 dark:text-amber-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['pending']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.reviews_pending') }}
                </p>
            </div>
        </a>

        {{-- Approved & Live --}}
        <a href="{{ route('store.admin.reviews.index', array_merge($baseParams, $currentSort, ['status' => 'approved'])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['approved'] }}">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['approved'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-emerald-600 dark:text-emerald-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['approved']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.reviews_approved') }}
                </p>
            </div>
        </a>

        {{-- Average Rating --}}
        <div role="listitem"
             class="w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['rating'] }} shadow-inner">
                <svg class="w-4 h-4 sm:w-4.5 sm:h-4.5 fill-amber-500 text-amber-500" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex items-baseline gap-1">
                    <p class="text-base sm:text-xl font-black text-sky-600 dark:text-sky-400 leading-none tabular-nums font-outfit">
                        {{ number_format($stats['avg_rating'], 1) }}
                    </p>
                    <span class="text-[10px] text-slate-400 font-bold">/ 5.0</span>
                </div>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.reviews_avg_rating') }}
                </p>
            </div>
        </div>

        {{-- 5-Star Reviews --}}
        <a href="{{ route('store.admin.reviews.index', array_merge($baseParams, $currentSort, ['rating' => 5])) }}" role="listitem"
           class="group w-full bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-2.5 sm:p-3 flex items-center gap-2 sm:gap-2.5 transition-all duration-200 hover:shadow-sm active:scale-[.99] {{ $statBorders['five_star'] }} col-span-2 sm:col-span-1">
            <div class="shrink-0 w-8 h-8 sm:w-9 sm:h-9 rounded-lg grid place-items-center {{ $statAccents['five_star'] }} shadow-inner">
                <span class="text-xs font-black">5★</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-base sm:text-xl font-black text-rose-600 dark:text-rose-400 leading-none tabular-nums font-outfit">
                    {{ number_format($stats['five_star']) }}
                </p>
                <p class="text-[10px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1 truncate font-bold uppercase tracking-wider">
                    {{ __('messages.reviews_5_star') }}
                </p>
            </div>
        </a>
    </div>

    {{-- ============================================================
         REUSABLE ADMIN TOOLBAR COMPONENT
         ============================================================ --}}
    <x-admin.toolbar
        :search="request('search', $search)"
        :searchPlaceholder="__('messages.search_by_name_sku_brand_category')"
        :sort="request('sort', $sort)"
        :sortOptions="[
            'newest'      => __('messages.reviews_sort_newest'),
            'oldest'      => __('messages.reviews_sort_oldest'),
            'rating_high' => __('messages.reviews_sort_rating_high'),
            'rating_low'  => __('messages.reviews_sort_rating_low'),
        ]"
        :filters="[
            'status' => [
                'label'   => __('messages.status'),
                'options' => [
                    'pending'  => __('messages.reviews_filter_pending'),
                    'approved' => __('messages.reviews_filter_approved'),
                ]
            ],
            'rating' => [
                'label'   => __('messages.reviews_avg_rating'),
                'options' => [
                    '5' => '5 ★★★★★',
                    '4' => '4 ★★★★☆',
                    '3' => '3 ★★★☆☆',
                    '2' => '2 ★★☆☆☆',
                    '1' => '1 ★☆☆☆☆',
                ]
            ],
        ]"
        :showViewToggle="true"
        :showExportImport="false"
        :paginator="$reviews"
        :perPageOptions="[20 => '20', 50 => '50', 100 => '100', 'all' => __('messages.all')]"
    />

    {{-- ============================================================
         TABLE VIEW — spreadsheet grid with sticky header
         ============================================================ --}}
    <div id="reviews-table" x-show="viewMode === 'table'" class="w-full bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-lg shadow-2xs overflow-hidden transition">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse font-sans text-slate-700 dark:text-slate-200">
                <thead class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b-2 border-slate-300 dark:border-slate-600 shadow-2xs select-none">
                    <tr class="text-[11px] font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider divide-x divide-slate-300 dark:divide-slate-700">
                        <th class="py-2.5 px-3 min-w-[170px]">{{ __('messages.reviews_reviewer_details') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[110px]">{{ __('messages.reviews_avg_rating') }}</th>
                        <th class="py-2.5 px-3 min-w-[190px]">{{ __('messages.reviews_product_details') }}</th>
                        <th class="py-2.5 px-3 min-w-[240px]">Feedback / Comment</th>
                        <th class="py-2.5 px-3 min-w-[120px]">{{ __('messages.date') }}</th>
                        <th class="py-2.5 px-3 text-center min-w-[110px]">{{ __('messages.status') }}</th>
                        <th class="py-2.5 pl-3 pr-4 text-right w-24">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/90 dark:divide-slate-800 bg-white dark:bg-slate-900">
                    @forelse($reviews as $r)
                        <tr class="hover:bg-amber-50/40 dark:hover:bg-amber-950/15 divide-x divide-slate-200/80 dark:divide-slate-800 transition-colors group">
                            {{-- Reviewer info --}}
                            <td class="py-2.5 px-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 grid place-items-center font-bold text-xs shrink-0">
                                        {{ mb_substr($r->reviewer_name, 0, 1) ?: 'U' }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 dark:text-slate-100 truncate text-xs">
                                            {{ $r->reviewer_name }}
                                        </div>
                                        @if($r->reviewer_phone)
                                            <div class="text-[11px] text-slate-400 dark:text-slate-500 font-mono mt-0.5">
                                                📞 {{ $r->reviewer_phone }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Rating Stars --}}
                            <td class="py-2.5 px-3 text-center">
                                <div class="inline-flex items-center gap-0.5 text-amber-500">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="{{ $i <= $r->rating ? 'text-amber-500' : 'text-slate-300 dark:text-slate-700' }} text-sm leading-none">★</span>
                                    @endfor
                                </div>
                                <div class="text-[10px] font-bold text-slate-400 font-mono mt-0.5">
                                    {{ $r->rating }} / 5
                                </div>
                            </td>

                            {{-- Product info --}}
                            <td class="py-2.5 px-3">
                                @if($r->product)
                                    <div class="flex items-center gap-2">
                                        @if($r->product->image_path)
                                            <img src="{{ asset('storage/' . $r->product->image_path) }}" alt="{{ $r->product->name }}"
                                                 class="w-7 h-7 rounded object-cover border border-slate-200/80 dark:border-slate-700 bg-slate-100 shrink-0">
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ route('storefront.product', ['store_slug' => $store->slug, 'slug' => $r->product->slug]) }}" target="_blank"
                                               class="font-bold text-xs text-sky-600 dark:text-sky-400 hover:underline truncate block max-w-xs">
                                                {{ $r->product->name }}
                                            </a>
                                            <div class="text-[10px] text-slate-400 font-mono">
                                                {{ $r->product->sku ?: 'No SKU' }}
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-xs">(Deleted Product)</span>
                                @endif
                            </td>

                            {{-- Comment / Feedback --}}
                            <td class="py-2.5 px-3">
                                @if($r->comment)
                                    <div class="text-xs text-slate-700 dark:text-slate-300 line-clamp-2 cursor-pointer"
                                         @click="openDetails({{ json_encode([
                                             'id' => $r->id,
                                             'name' => $r->reviewer_name,
                                             'phone' => $r->reviewer_phone,
                                             'rating' => $r->rating,
                                             'comment' => $r->comment,
                                             'product' => $r->product?->name ?? '(Deleted Product)',
                                             'product_slug' => $r->product?->slug ?? '',
                                             'product_img' => $r->product?->image_path ? asset('storage/' . $r->product->image_path) : null,
                                             'is_approved' => $r->is_approved,
                                             'created_at' => $r->created_at->format('d M Y, h:i A')
                                         ]) }})"
                                         title="Click to view full comment">
                                        "{{ $r->comment }}"
                                    </div>
                                @else
                                    <span class="text-slate-400 text-xs italic">No written comment</span>
                                @endif
                            </td>

                            {{-- Date --}}
                            <td class="py-2.5 px-3 text-[11px] font-mono text-slate-500 dark:text-slate-400">
                                <div>{{ $r->created_at->format('d M Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $r->created_at->format('h:i A') }}</div>
                            </td>

                            {{-- Status Badge --}}
                            <td class="py-2.5 px-3 text-center">
                                @if($r->is_approved)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        {{ __('messages.reviews_approved_badge') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                        {{ __('messages.reviews_pending_badge') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="py-2.5 pl-3 pr-4 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Toggle Approve / Hide --}}
                                    <form method="POST" action="{{ route('store.admin.reviews.approve', ['store_slug' => $store->slug, 'review' => $r->id]) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                                class="px-2 py-1 rounded-lg text-xs font-bold transition {{ $r->is_approved ? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700' : 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs' }} active:scale-95"
                                                title="{{ $r->is_approved ? __('messages.reviews_hide_btn') : __('messages.reviews_approve_btn') }}">
                                            {{ $r->is_approved ? __('messages.reviews_hide_btn') : __('messages.reviews_approve_btn') }}
                                        </button>
                                    </form>

                                    {{-- Delete --}}
                                    <form method="POST" action="{{ route('store.admin.reviews.destroy', ['store_slug' => $store->slug, 'review' => $r->id]) }}"
                                          onsubmit="return confirm('{{ __('messages.reviews_delete_confirm') }}')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                                                title="{{ __('messages.delete') }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="max-w-sm mx-auto space-y-2.5">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                                        <span class="text-xl">⭐</span>
                                    </div>
                                    <p class="font-bold text-xs text-slate-700 dark:text-slate-300">{{ __('messages.reviews_empty') }}</p>
                                    <p class="text-[11px] text-slate-400">{{ __('messages.reviews_empty_desc') }}</p>
                                    <a href="{{ $clearFiltersUrl }}" class="inline-block text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline">
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
         CARD / FEED VIEW — testimonial review cards
         ============================================================ --}}
    <div id="reviews-cards" x-show="viewMode === 'card'" class="w-full">
        @if($reviews->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2.5">
                @foreach($reviews as $r)
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 shadow-2xs hover:shadow-sm transition flex flex-col justify-between space-y-2.5">
                        <div class="space-y-2.5">
                            {{-- Reviewer Top Row --}}
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 grid place-items-center font-bold text-xs shrink-0">
                                        {{ mb_substr($r->reviewer_name, 0, 1) ?: 'U' }}
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-xs text-slate-900 dark:text-slate-100 truncate">{{ $r->reviewer_name }}</h3>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $r->created_at->format('d M Y') }}</div>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold {{ $r->is_approved ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' }}">
                                    {{ $r->is_approved ? __('messages.reviews_approved_badge') : __('messages.reviews_pending_badge') }}
                                </span>
                            </div>

                            {{-- Stars + Product --}}
                            <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                                <div class="inline-flex items-center gap-0.5 text-amber-500 text-xs">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="{{ $i <= $r->rating ? 'text-amber-500' : 'text-slate-300 dark:text-slate-700' }}">★</span>
                                    @endfor
                                    <span class="text-[10px] font-bold text-slate-400 ml-1 font-mono">({{ $r->rating }})</span>
                                </div>
                                @if($r->reviewer_phone)
                                    <span class="text-[10px] text-slate-400 font-mono">📞 {{ $r->reviewer_phone }}</span>
                                @endif
                            </div>

                            {{-- Product Pill --}}
                            @if($r->product)
                                <a href="{{ route('storefront.product', ['store_slug' => $store->slug, 'slug' => $r->product->slug]) }}" target="_blank"
                                   class="p-1.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 flex items-center gap-2 group/prod hover:border-sky-300 dark:hover:border-sky-700 transition">
                                    @if($r->product->image_path)
                                        <img src="{{ asset('storage/' . $r->product->image_path) }}" alt="{{ $r->product->name }}"
                                             class="w-6 h-6 rounded object-cover shrink-0">
                                    @endif
                                    <span class="text-[11px] font-bold text-slate-700 dark:text-slate-300 group-hover/prod:text-sky-600 truncate flex-1">
                                        {{ $r->product->name }}
                                    </span>
                                    <span class="text-[10px] text-slate-400">↗</span>
                                </a>
                            @endif

                            {{-- Comment text --}}
                            @if($r->comment)
                                <p class="text-xs text-slate-700 dark:text-slate-300 italic bg-slate-50/50 dark:bg-slate-800/40 p-2 rounded-lg border border-slate-100 dark:border-slate-800 line-clamp-3">
                                    "{{ $r->comment }}"
                                </p>
                            @endif
                        </div>

                        {{-- Card Bottom Actions --}}
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                            <form method="POST" action="{{ route('store.admin.reviews.approve', ['store_slug' => $store->slug, 'review' => $r->id]) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $r->is_approved ? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200' : 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-2xs' }} active:scale-95">
                                    {{ $r->is_approved ? __('messages.reviews_hide_btn') : __('messages.reviews_approve_btn') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('store.admin.reviews.destroy', ['store_slug' => $store->slug, 'review' => $r->id]) }}"
                                  onsubmit="return confirm('{{ __('messages.reviews_delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition" title="{{ __('messages.delete') }}">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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
                        <span class="text-xl">⭐</span>
                    </div>
                    <p class="font-bold text-xs text-slate-700 dark:text-slate-300">{{ __('messages.reviews_empty') }}</p>
                    <p class="text-[11px] text-slate-400">{{ __('messages.reviews_empty_desc') }}</p>
                    <a href="{{ $clearFiltersUrl }}" class="inline-block text-xs font-bold text-sky-600 dark:text-sky-400 hover:underline">
                        {{ __('messages.clear_all') }}
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- ============================================================
         DETAILS MODAL
         ============================================================ --}}
    <div x-show="detailsModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <template x-if="selectedReview">
            <div @click.away="detailsModal = false"
                 class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-2xl max-w-md w-full p-4 sm:p-5 space-y-3.5">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-950/70 dark:text-violet-300 grid place-items-center font-bold text-xs">
                            <span x-text="selectedReview.name ? selectedReview.name.charAt(0) : 'U'"></span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100" x-text="selectedReview.name"></h3>
                            <p class="text-[11px] text-slate-400" x-text="selectedReview.created_at"></p>
                        </div>
                    </div>
                    <button type="button" @click="detailsModal = false" class="p-1 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    {{-- Star Rating + Phone --}}
                    <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">{{ __('messages.reviews_avg_rating') }}</span>
                            <div class="flex items-center gap-1 text-amber-500 text-base">
                                <span class="font-black text-slate-900 dark:text-slate-100 font-mono" x-text="selectedReview.rating + ' / 5'"></span>
                                <span>★</span>
                            </div>
                        </div>
                        <template x-if="selectedReview.phone">
                            <div class="text-right">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">{{ __('messages.phone') }}</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200" x-text="selectedReview.phone"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Product --}}
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">{{ __('messages.reviews_product_details') }}</span>
                        <div class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-200/60 dark:border-slate-700/60 flex items-center gap-2">
                            <template x-if="selectedReview.product_img">
                                <img :src="selectedReview.product_img" class="w-8 h-8 rounded object-cover">
                            </template>
                            <span class="font-bold text-slate-900 dark:text-slate-100" x-text="selectedReview.product"></span>
                        </div>
                    </div>

                    {{-- Feedback Comment --}}
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Feedback / Comment</span>
                        <div class="p-3 rounded-lg bg-slate-50 dark:bg-slate-800/40 border border-slate-200/60 dark:border-slate-700/60 text-slate-800 dark:text-slate-200 whitespace-pre-line leading-relaxed"
                             x-text="selectedReview.comment || 'No written comment.'">
                        </div>
                    </div>
                </div>

                <div class="pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <button type="button" @click="detailsModal = false" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition active:scale-95">
                        {{ __('messages.close') }}
                    </button>
                    <form method="POST" :action="'{{ url('/store/' . $store->slug . '/admin/reviews') }}/' + selectedReview.id + '/approve'">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="px-4 py-1.5 rounded-lg text-xs font-bold text-white shadow-2xs transition active:scale-95"
                                :class="selectedReview.is_approved ? 'bg-slate-600 hover:bg-slate-700' : 'bg-emerald-600 hover:bg-emerald-700'">
                            <span x-text="selectedReview.is_approved ? '{{ __('messages.reviews_hide_btn') }}' : '{{ __('messages.reviews_approve_btn') }}'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </template>
    </div>

</div>
@endsection
