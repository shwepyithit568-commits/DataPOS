<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\StoreContext;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request, StoreContext $context)
    {
        $store = $context->getStore();
        $query = Post::published();

        if ($store) {
            $query->where('store_id', $store->id);
        }

        // Category filter (?category=Tips & Tricks)
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $posts = $query->latest('published_at')->paginate(9)->withQueryString();

        // All categories used by this store — for the filter chips.
        $categories = Post::published()
            ->where('store_id', $store?->id ?? 0)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('storefront.blog.index', compact('posts', 'store', 'categories'));
    }

    public function show(Request $request, StoreContext $context, string $slug)
    {
        $store = $context->getStore();
        $query = Post::published()->where('slug', $slug);

        if ($store) {
            $query->where('store_id', $store->id);
        }

        $post = $query->firstOrFail();

        $related = Post::published()
            ->where('store_id', $store?->id ?? $post->store_id)
            ->where('id', '!=', $post->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        // Prev / next article by publish date (keeps readers on the blog).
        $publishedAt = $post->published_at ?? $post->created_at;
        $prevPost = Post::published()
            ->where('store_id', $store?->id ?? $post->store_id)
            ->where(fn ($q) => $q->where('published_at', '<', $publishedAt)->orWhereNull('published_at'))
            ->latest('published_at')
            ->first();
        $nextPost = Post::published()
            ->where('store_id', $store?->id ?? $post->store_id)
            ->where('published_at', '>', $publishedAt)
            ->oldest('published_at')
            ->first();

        // SEO meta (consumed by the storefront layout <head>).
        $title = $post->title;
        $ogTitle = $post->title;
        $metaDescription = $post->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 160);
        $metaKeywords = $post->meta_keywords;

        return view('storefront.blog.show', compact('post', 'related', 'prevPost', 'nextPost', 'store', 'title', 'ogTitle', 'metaDescription', 'metaKeywords'));
    }
}
