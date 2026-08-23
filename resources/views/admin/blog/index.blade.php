@extends('layouts.admin.app')

@section('content')
<div class="w-full space-y-5 sm:space-y-6">
    {{-- Header --}}
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Blog Posts</h1>
            <p class="admin-page-sub">{{ $store->name }} · {{ number_format($posts->total()) }} posts</p>
        </div>
        <a href="{{ route('store.admin.blog.create', ['store_slug' => $store->slug]) }}"
            class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-xl bg-violet-600 px-4 py-2 text-sm font-black text-white shadow-md shadow-violet-500/25 hover:bg-violet-700 transition">
            <span>+</span> Add Post
        </a>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-700 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- Standard Admin Toolbar --}}
    <x-admin.toolbar
        :search="request('search', '')"
        :searchPlaceholder="'Search title or slug…'"
        :sort="request('sort', 'newest')"
        :sortOptions="[
            'newest' => 'Newest first',
            'oldest' => 'Oldest first',
            'title_asc' => 'Title A–Z',
        ]"
        :filters="[
            'status' => [
                'label' => 'Status',
                'options' => [
                    'published' => 'Published',
                    'draft' => 'Draft',
                ],
            ],
        ]"
        :showViewToggle="false"
        :showExportImport="false"
        :totalCount="$posts->total()"
        :paginator="$posts"
    />

    {{-- List --}}
    <div class="admin-panel overflow-hidden">
        @forelse ($posts as $post)
            <div class="flex items-center gap-3 sm:gap-4 p-3 sm:p-4 border-b border-gray-100 dark:border-slate-700/60 last:border-0 hover:bg-gray-50/70 dark:hover:bg-slate-700/30 transition">
                <div class="hidden sm:flex w-16 h-12 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-700 flex-shrink-0 items-center justify-center text-xl">
                    @if ($post->image_path)
                        <img src="{{ asset('storage/' . $post->image_path) }}" alt="" class="w-full h-full object-cover" data-img-fallback="hide" />
                    @else
                        📝
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <a href="{{ route('store.admin.blog.edit', ['store_slug' => $store->slug, 'post' => $post->id]) }}"
                        class="font-bold text-sm text-gray-900 dark:text-slate-100 hover:text-violet-600 dark:hover:text-violet-400 transition line-clamp-1">
                        {{ $post->title }}
                    </a>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5 font-mono truncate">
                        /blog/{{ $post->slug }}
                        <span class="mx-1">·</span>
                        {{ $post->published_at?->format('M j, Y') ?? $post->created_at->format('M j, Y') }}
                    </p>
                </div>
                <span class="shrink-0 px-2.5 py-1 text-xs font-extrabold rounded-full uppercase {{ $post->is_published ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300' : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400' }}">
                    {{ $post->is_published ? 'Published' : 'Draft' }}
                </span>
                <div class="shrink-0 flex items-center gap-1">
                    <a href="{{ url('/store/' . $store->slug . '/blog/' . $post->slug) }}" target="_blank" title="View"
                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-sky-600 hover:bg-sky-50 dark:hover:bg-sky-950/40 transition">👁️</a>
                    <a href="{{ route('store.admin.blog.edit', ['store_slug' => $store->slug, 'post' => $post->id]) }}" title="Edit"
                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-950/40 transition">✏️</a>
                    <form method="POST" action="{{ route('store.admin.blog.destroy', ['store_slug' => $store->slug, 'post' => $post->id]) }}"
                        data-confirm="Delete this blog post?">
                        @csrf @method('DELETE')
                        <button type="submit" title="Delete"
                            class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition">🗑️</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-gray-400 dark:text-slate-500">
                <div class="text-4xl mb-2">📝</div>
                <p class="font-semibold">No blog posts yet.</p>
                <a href="{{ route('store.admin.blog.create', ['store_slug' => $store->slug]) }}" class="text-violet-600 dark:text-violet-400 font-bold text-sm mt-1 inline-block">Write your first post →</a>
            </div>
        @endforelse
    </div>

    <div>{{ $posts->links() }}</div>
</div>
@endsection
