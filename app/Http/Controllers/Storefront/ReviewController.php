<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Store a customer review for a product. Reviews start unapproved and
     * only appear on the storefront after the owner approves them in Admin
     * (Content → Product Reviews).
     */
    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        // Use route('slug') explicitly — the method param would otherwise be
        // filled positionally with {store_slug} by Laravel's DI (same as the
        // settings sections bug).
        $slug = $request->route('slug');

        abort_unless($store, 404, 'Store not found.');

        $product = Product::where('store_id', $store->id)->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'reviewer_name'  => ['required', 'string', 'max:100'],
            'reviewer_phone' => ['nullable', 'string', 'max:30'],
            'rating'         => ['required', 'integer', 'between:1,5'],
            'comment'        => ['nullable', 'string', 'max:2000'],
        ]);

        Review::create([
            ...$validated,
            'store_id'   => $store->id,
            'product_id' => $product->id,
            'user_id'    => auth()->id(),
            'is_approved' => false,
        ]);

        return back()->with('review_success', __('messages.review_submitted'));
    }
}
