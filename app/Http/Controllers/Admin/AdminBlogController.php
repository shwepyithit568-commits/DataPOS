<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\ImageOptimizer;
use App\Services\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminBlogController extends Controller
{
    public function index(Request $request, StoreContext $context): View
    {
        $store = $context->getStore();
        abort_if(!$store, 404);

        $query = Post::where('store_id', $store->id);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%')
                  ->orWhere('excerpt', 'like', '%' . $search . '%')
                  ->orWhere('category', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false);
            }
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest'     => $query->oldest(),
            'title_asc'  => $query->orderBy('title', 'asc'),
            default      => $query->latest('published_at')->latest('id'),
        };

        $perPage = request('per_page') === 'all' ? 100000 : (int) request('per_page', 20);
        $posts = $query->paginate($perPage)->withQueryString();

        // Calculate KPI summary stats
        $allPosts = Post::where('store_id', $store->id)->get();
        $stats = [
            'total'            => $allPosts->count(),
            'published'        => $allPosts->where('is_published', true)->count(),
            'draft'            => $allPosts->where('is_published', false)->count(),
            'categories_count' => $allPosts->whereNotNull('category')->pluck('category')->unique()->count(),
        ];

        $categories = $allPosts->whereNotNull('category')->pluck('category')->unique()->values()->all();

        return view('admin.blog.index', compact('store', 'posts', 'stats', 'categories'));
    }

    public function create(StoreContext $context): View
    {
        $store = $context->getStore();
        $post = new Post(['store_id' => $store->id]);

        return view('admin.blog.form', compact('store', 'post'));
    }

    public function store(Request $request, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();
        $validated = $this->validateData($request, $store->id);

        if ($request->hasFile('image')) {
            $validated['image_path'] = ImageOptimizer::store($request->file('image'), 'blog', 1400);
        }
        unset($validated['image']);

        $post = Post::create([...$validated, 'store_id' => $store->id]);

        return redirect()
            ->route('store.admin.blog.edit', ['store_slug' => $store->slug, 'post' => $post->id])
            ->with('success', 'Blog post created successfully.');
    }

    public function edit(string $store_slug, Post $post, StoreContext $context): View
    {
        $store = $context->getStore();

        if ($post->store_id !== $store->id) {
            abort(403, 'Unauthorized store post access.');
        }

        return view('admin.blog.form', compact('store', 'post'));
    }

    public function update(Request $request, string $store_slug, Post $post, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($post->store_id !== $store->id) {
            abort(403, 'Unauthorized store post access.');
        }

        $validated = $this->validateData($request, $store->id, $post->id);

        if ($request->hasFile('image')) {
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            $validated['image_path'] = ImageOptimizer::store($request->file('image'), 'blog', 1400);
        }
        unset($validated['image']);

        $post->update($validated);

        return back()->with('success', 'Blog post updated successfully.');
    }

    public function destroy(string $store_slug, Post $post, StoreContext $context): RedirectResponse
    {
        $store = $context->getStore();

        if ($post->store_id !== $store->id) {
            abort(403, 'Unauthorized store post access.');
        }

        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete();

        return back()->with('success', 'Blog post deleted.');
    }

    private function validateData(Request $request, int $storeId, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'title'            => ['required', 'string', 'max:255'],
            'slug'             => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'category'         => ['nullable', 'string', 'max:100'],
            'excerpt'          => ['nullable', 'string', 'max:500'],
            'content'          => ['required', 'string'],
            'tags'             => ['nullable', 'string', 'max:255'],
            'meta_keywords'    => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'image'            => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'is_published'     => ['nullable', 'boolean'],
            'published_at'     => ['nullable', 'date'],
        ]);

        // Auto-generate slug from the title when left blank, then keep it unique per store.
        $slug = !empty($validated['slug']) ? $validated['slug'] : Str::slug($validated['title']);
        $baseSlug = $slug;
        $counter = 2;
        while (Post::where('store_id', $storeId)->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }
        $validated['slug'] = $slug;

        $validated['is_published'] = $request->boolean('is_published');
        if ($validated['is_published'] && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        // Normalize tags to a comma-separated string (single values + chips both fine).
        if (!empty($validated['tags']) && is_array($validated['tags'])) {
            $validated['tags'] = implode(', ', array_filter(array_map('trim', $validated['tags'])));
        }

        return $validated;
    }
}
