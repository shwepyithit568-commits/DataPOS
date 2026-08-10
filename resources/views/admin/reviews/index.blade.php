@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6">
    {{-- Header --}}
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Product Reviews</h1>
            <p class="admin-page-sub">{{ $store->name }} · {{ number_format($reviews->total()) }} reviews
                @if ($pendingCount > 0)<span class="ml-1 font-semibold text-rose-600 dark:text-rose-400">· {{ $pendingCount }} pending</span>@endif
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-700 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <select name="status" class="rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm text-gray-900 dark:text-slate-100 shadow-sm cursor-pointer">
            <option value="">All statuses</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending approval</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
        </select>
        <select name="rating" class="rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm text-gray-900 dark:text-slate-100 shadow-sm cursor-pointer">
            <option value="">All ratings</option>
            @foreach ([5, 4, 3, 2, 1] as $r)
                <option value="{{ $r }}" {{ request('rating') == $r ? 'selected' : '' }}>{{ $r }} ★</option>
            @endforeach
        </select>
        <select name="sort" class="rounded-xl border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2.5 text-sm text-gray-900 dark:text-slate-100 shadow-sm cursor-pointer">
            <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Newest first</option>
            <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
            <option value="rating_high" {{ request('sort') === 'rating_high' ? 'selected' : '' }}>Highest rating</option>
        </select>
        <button type="submit" class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-amber-700">Filter</button>
        @if (request()->hasAny(['status', 'rating', 'sort']))
            <a href="{{ route('store.admin.reviews.index', ['store_slug' => $store->slug]) }}" class="rounded-xl px-3 py-2.5 text-sm font-semibold text-gray-500 hover:text-gray-800 dark:hover:text-slate-200">Clear</a>
        @endif
    </form>

    {{-- List --}}
    <div class="admin-panel overflow-hidden">
        @forelse ($reviews as $review)
            <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-slate-700/60 last:border-0 hover:bg-gray-50/70 dark:hover:bg-slate-700/30 transition">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-sm text-gray-900 dark:text-slate-100">{{ $review->reviewer_name }}</span>
                            <span class="text-amber-500 text-xs tracking-tight">
                                @for ($i = 1; $i <= 5; $i++)
                                    <span class="{{ $i <= $review->rating ? '' : 'opacity-25' }}">★</span>
                                @endfor
                            </span>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full uppercase {{ $review->is_approved ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300' }}">
                                {{ $review->is_approved ? 'Approved' : 'Pending' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
                            <a href="{{ url('/store/' . $store->slug . '/product/' . ($review->product?->slug ?? '')) }}" target="_blank" class="font-semibold text-violet-600 dark:text-violet-400 hover:underline">
                                {{ $review->product?->name ?? '(deleted product)' }}
                            </a>
                            @if ($review->reviewer_phone) <span class="mx-1">·</span> 📞 {{ $review->reviewer_phone }} @endif
                            <span class="mx-1">·</span> {{ $review->created_at->format('M j, Y') }}
                        </p>
                        @if ($review->comment)
                            <p class="mt-2 text-sm text-gray-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $review->comment }}</p>
                        @endif
                    </div>
                    <div class="shrink-0 flex items-center gap-1.5">
                        <form method="POST" action="{{ route('store.admin.reviews.approve', ['store_slug' => $store->slug, 'review' => $review->id]) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $review->is_approved ? 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-600' : 'bg-emerald-600 text-white hover:bg-emerald-700' }}">
                                {{ $review->is_approved ? 'Hide' : '✓ Approve' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('store.admin.reviews.destroy', ['store_slug' => $store->slug, 'review' => $review->id]) }}"
                            data-confirm="Delete this review?">
                            @csrf @method('DELETE')
                            <button type="submit" title="Delete"
                                class="px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-xs font-bold transition">
                                🗑️
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-gray-400 dark:text-slate-500">
                <div class="text-4xl mb-2">⭐</div>
                <p class="font-semibold">No reviews yet.</p>
                <p class="text-xs mt-1">Customers can leave reviews on each product page — approve them here.</p>
            </div>
        @endforelse
    </div>

    <div>{{ $reviews->links() }}</div>
</div>
@endsection
