<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminReviewController extends Controller
{
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $query = Review::where('store_id', $store->id)->with(['product']);

        if ($search = trim((string) $request->input('search', $request->input('q', '')))) {
            $query->where(function ($q) use ($search) {
                $q->where('reviewer_name', 'like', "%{$search}%")
                    ->orWhere('reviewer_phone', 'like', "%{$search}%")
                    ->orWhere('comment', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('is_approved', false);
            } elseif ($request->status === 'approved') {
                $query->where('is_approved', true);
            }
        }

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->rating);
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest'      => $query->oldest(),
            'rating_high' => $query->orderByDesc('rating')->latest(),
            'rating_low'  => $query->orderBy('rating', 'asc')->latest(),
            default       => $query->latest(),
        };

        $perPage = $request->input('per_page') === 'all' || $request->input('limit') === 'all'
            ? 1000
            : (int) $request->input('per_page', $request->input('limit', 20));

        $reviews = $query->paginate($perPage)->withQueryString();

        // Summary KPI stats
        $totalReviews = Review::where('store_id', $store->id)->count();
        $pendingCount = Review::where('store_id', $store->id)->where('is_approved', false)->count();
        $approvedCount = Review::where('store_id', $store->id)->where('is_approved', true)->count();
        $avgRating = Review::where('store_id', $store->id)->avg('rating');
        $fiveStarCount = Review::where('store_id', $store->id)->where('rating', 5)->count();

        $stats = [
            'total'      => $totalReviews,
            'pending'    => $pendingCount,
            'approved'   => $approvedCount,
            'avg_rating' => $avgRating ? round((float) $avgRating, 1) : 0,
            'five_star'  => $fiveStarCount,
        ];

        return view('admin.reviews.index', compact('store', 'reviews', 'stats', 'pendingCount', 'search', 'sort'));
    }

    public function toggleApprove(string $store_slug, Review $review, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        if ($review->store_id !== $store->id) {
            abort(403, 'Unauthorized store review access.');
        }

        $review->update(['is_approved' => ! $review->is_approved]);

        return back()->with('success', $review->is_approved
            ? __('messages.review_approved_success')
            : __('messages.review_hidden_success'));
    }

    public function destroy(string $store_slug, Review $review, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        if ($review->store_id !== $store->id) {
            abort(403, 'Unauthorized store review access.');
        }

        $review->delete();

        return back()->with('success', __('messages.review_deleted_success'));
    }
}
