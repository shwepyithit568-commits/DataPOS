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

        $query = Review::where('store_id', $store->id)->with(['product']);

        if ($search = trim((string) $request->input('search', $request->input('q', '')))) {
            $query->where(function ($q) use ($search) {
                $q->where('reviewer_name', 'like', "%{$search}%")
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
            'oldest'     => $query->oldest(),
            'rating_high' => $query->orderByDesc('rating'),
            default      => $query->latest(),
        };

        $perPage = request('per_page') === 'all' ? 100000 : (int) request('per_page', 25);
        $reviews = $query->paginate($perPage)->withQueryString();

        $pendingCount = Review::where('store_id', $store->id)->where('is_approved', false)->count();

        return view('admin.reviews.index', compact('store', 'reviews', 'pendingCount', 'search', 'sort'));
    }

    public function toggleApprove(string $store_slug, Review $review, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($review->store_id !== $store->id) {
            abort(403, 'Unauthorized store review access.');
        }

        $review->update(['is_approved' => ! $review->is_approved]);

        return back()->with('success', $review->is_approved ? 'Review approved — now visible on the product page.' : 'Review hidden from the product page.');
    }

    public function destroy(string $store_slug, Review $review, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($review->store_id !== $store->id) {
            abort(403, 'Unauthorized store review access.');
        }

        $review->delete();

        return back()->with('success', 'Review deleted.');
    }
}
