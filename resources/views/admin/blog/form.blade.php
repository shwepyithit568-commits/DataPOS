@extends('layouts.admin.app')

@section('content')
@php
    // Clean, borderless admin form styling — light underline inputs, no card boxes.
    $inputClass = 'mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-100';
    $labelClass = 'block text-xs font-black uppercase tracking-wide text-gray-500 dark:text-slate-400';
    $helpClass = 'mt-1 text-xs text-gray-400 dark:text-slate-500';
    $isEdit = $post->exists;
    $contentValue = old('content', $post->content);
    $hasExistingContent = filled($contentValue);
    $initialBlocks = $hasExistingContent
        ? [['type' => 'html', 'html' => $contentValue]]
        : [['type' => 'paragraph', 'text' => '']];
    $categoryOptions = array_values(array_unique(array_filter(array_merge(
        ['Tips & Tricks', 'How-to Guide', 'Product News', 'Announcements'],
        \App\Models\Post::where('store_id', $store->id)->whereNotNull('category')->pluck('category')->all()
    ))));
    $previewUrl = $isEdit ? url('/blog/' . $post->slug . '?store_slug=' . $store->slug) : null;
@endphp

{{-- Full-width, borderless Content Studio — sections separated by whitespace only --}}
<div class="w-full"
    x-data="blogBlockEditor({
        initialBlocks: @js($initialBlocks),
        initialStatus: @js((bool) old('is_published', $post->is_published)),
    })">

    {{-- Page header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-black uppercase tracking-widest text-violet-600 dark:text-violet-300">Content Studio</p>
            <h1 class="mt-1 text-2xl font-black text-gray-900 dark:text-slate-100 font-outfit sm:text-3xl">
                {{ $isEdit ? 'Edit Blog Post' : 'New Blog Post' }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
                WordPress + Notion style block editor for product tips, news, guides and campaigns.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($isEdit)
                <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer"
                    class="inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-sm font-bold text-sky-600 transition hover:bg-sky-50 dark:text-sky-400 dark:hover:bg-sky-500/10">
                    Preview ↗
                </a>
            @endif
            <a href="{{ route('store.admin.blog.index', ['store_slug' => $store->slug]) }}"
                class="inline-flex min-h-10 items-center justify-center rounded-lg px-4 py-2 text-sm font-bold text-gray-500 transition hover:bg-gray-100 dark:text-slate-400 dark:hover:bg-slate-800">
                ← Back to posts
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="mt-4 rounded-lg border-l-4 border-green-500 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700 dark:bg-green-950/40 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-4 rounded-lg border-l-4 border-rose-500 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
            <p class="font-bold">Please check the highlighted fields.</p>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data"
        action="{{ $isEdit ? route('store.admin.blog.update', ['store_slug' => $store->slug, 'post' => $post->id]) : route('store.admin.blog.store', ['store_slug' => $store->slug]) }}"
        class="mt-8 grid grid-cols-1 gap-x-10 gap-y-6 lg:grid-cols-[minmax(0,1fr)_17rem]"
        @submit="syncContent()">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        {{-- Main column --}}
        <div class="space-y-6 min-w-0">
            {{-- Title & meta --}}
            <section class="space-y-6">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-[minmax(0,1fr)_11rem_9rem]">
                    <div>
                        <label class="{{ $labelClass }}">Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $post->title) }}" required class="{{ $inputClass }}" placeholder="Enter title" />
                        @error('title')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Category</label>
                        <select name="category" class="{{ $inputClass }} cursor-pointer">
                            <option value="">None</option>
                            @foreach ($categoryOptions as $cat)
                                <option value="{{ $cat }}" {{ old('category', $post->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('category')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Published</label>
                        <label class="mt-1 flex min-h-11 items-center justify-between rounded-lg border border-slate-200 px-3 text-sm font-bold text-gray-700 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200">
                            <span x-text="isPublished ? 'Published' : 'Draft'"></span>
                            <input type="checkbox" name="is_published" value="1" x-model="isPublished" class="h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500" />
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-[minmax(0,1fr)_14rem]">
                    <div>
                        <label class="{{ $labelClass }}">Featured Image</label>
                        <input type="file" name="image" accept="image/*"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-violet-50 file:px-4 file:py-2.5 file:text-sm file:font-bold file:text-violet-700 hover:file:bg-violet-100 dark:text-slate-400 dark:file:bg-slate-800 dark:file:text-violet-300" />
                        <p class="{{ $helpClass }}">Recommended 1200 x 750, under 5MB.</p>
                        @error('image')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="rounded-lg bg-gray-50 p-2 dark:bg-slate-900/40">
                        @if (!empty($post->image_path))
                            <img src="{{ asset('storage/' . $post->image_path) }}" alt="Preview" class="w-full aspect-[16/10] rounded-md object-cover" data-img-fallback="hide" />
                        @else
                            <div class="flex aspect-[16/10] items-center justify-center rounded-md border border-dashed border-gray-300 text-xs font-bold text-gray-400 dark:border-slate-600 dark:text-slate-500">No image</div>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Block editor --}}
            <section>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-gray-900 dark:text-slate-100">Content</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Add blocks, reorder them, then publish.</p>
                    </div>
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            class="inline-flex min-h-11 items-center justify-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-black text-white transition hover:bg-violet-700">
                            + Add Block
                        </button>
                        <div x-cloak x-show="open" x-transition
                            class="absolute right-0 z-10 mt-2 w-56 overflow-hidden rounded-xl bg-white p-1 shadow-xl dark:border dark:border-slate-700 dark:bg-slate-900">
                            <template x-for="item in blockTypes" :key="item.type">
                                <button type="button" @click="addBlock(item.type); open = false"
                                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-bold text-gray-700 hover:bg-violet-50 dark:text-slate-200 dark:hover:bg-slate-800">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-xs text-gray-600 dark:bg-slate-800 dark:text-slate-300" x-text="item.short"></span>
                                    <span x-text="item.label"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Blocks — open list, no card boxes --}}
                <div class="mt-5 space-y-4">
                    <template x-for="(block, index) in blocks" :key="block.id">
                        <article class="group border-b border-slate-100 pb-4 dark:border-slate-800/70">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-xs font-black text-violet-600 dark:bg-violet-950/60 dark:text-violet-300" x-text="blockLabel(block.type).short"></span>
                                    <div class="min-w-0">
                                        <h3 class="truncate text-sm font-black text-gray-800 dark:text-slate-100" x-text="blockLabel(block.type).label"></h3>
                                        <p class="text-xs font-semibold text-gray-400">Block #<span x-text="index + 1"></span></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="moveBlock(index, -1)" class="h-8 w-8 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-slate-800 dark:hover:text-slate-100" aria-label="Move up">↑</button>
                                    <button type="button" @click="moveBlock(index, 1)" class="h-8 w-8 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-slate-800 dark:hover:text-slate-100" aria-label="Move down">↓</button>
                                    <button type="button" @click="removeBlock(index)" class="h-8 w-8 rounded-lg text-rose-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40" aria-label="Delete">×</button>
                                </div>
                            </div>

                            <textarea x-show="block.type === 'paragraph'" x-model="block.text" rows="4" class="{{ $inputClass }}" placeholder="Write paragraph..."></textarea>
                            <input x-show="block.type === 'heading'" x-model="block.text" type="text" class="{{ $inputClass }}" placeholder="Heading text" />
                            <textarea x-show="block.type === 'quote'" x-model="block.text" rows="3" class="{{ $inputClass }}" placeholder="Quote text"></textarea>
                            <div x-show="block.type === 'image'" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <input x-model="block.url" type="url" class="{{ $inputClass }}" placeholder="Image URL" />
                                <input x-model="block.alt" type="text" class="{{ $inputClass }}" placeholder="Alt text" />
                            </div>
                            <div x-show="block.type === 'button'" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <input x-model="block.text" type="text" class="{{ $inputClass }}" placeholder="Button label" />
                                <input x-model="block.url" type="url" class="{{ $inputClass }}" placeholder="Button URL" />
                            </div>
                            <input x-show="block.type === 'video'" x-model="block.url" type="url" class="{{ $inputClass }}" placeholder="YouTube, TikTok or video URL" />
                            <textarea x-show="block.type === 'html'" x-model="block.html" rows="8" class="{{ $inputClass }} font-mono" placeholder="Existing HTML content"></textarea>
                        </article>
                    </template>
                    <p x-show="blocks.length === 0" class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm font-bold text-gray-400 dark:border-slate-700 dark:text-slate-500">
                        No blocks yet. Add your first block.
                    </p>
                    <textarea name="content" x-ref="contentInput" class="hidden">{{ $contentValue }}</textarea>
                    @error('content')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </section>

            {{-- SEO --}}
            <section>
                <h2 class="text-lg font-black text-gray-900 dark:text-slate-100">SEO</h2>
                <div class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $post->slug) }}" class="{{ $inputClass }}" placeholder="auto-from-title" />
                        <p class="{{ $helpClass }}">Leave blank to auto-generate from title.</p>
                        @error('slug')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Keywords</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $post->meta_keywords) }}" class="{{ $inputClass }}" placeholder="phone, repair, accessories" />
                        @error('meta_keywords')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="{{ $labelClass }}">Tags</label>
                        <input type="text" name="tags" value="{{ old('tags', $post->tags) }}" class="{{ $inputClass }}" placeholder="tips, charging, cctv" />
                        @error('tags')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Excerpt</label>
                        <textarea name="excerpt" rows="2" maxlength="500" class="{{ $inputClass }}" placeholder="Short summary for blog list">{{ old('excerpt', $post->excerpt) }}</textarea>
                        @error('excerpt')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="mt-5">
                    <label class="{{ $labelClass }}">Meta Description</label>
                    <textarea name="meta_description" rows="3" maxlength="1000" class="{{ $inputClass }}" placeholder="Google/search share summary">{{ old('meta_description', $post->meta_description) }}</textarea>
                    @error('meta_description')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </section>
        </div>

        {{-- Side column — publish actions --}}
        <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start min-w-0">
            <section class="rounded-2xl border border-slate-200/70 bg-white/60 p-4 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/40">
                <h2 class="text-sm font-black text-gray-900 dark:text-slate-100">Publish</h2>
                <label class="mt-4 block">
                    <span class="{{ $labelClass }}">Schedule / Publish Date</span>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="{{ $inputClass }}" />
                    <span class="{{ $helpClass }}">If Published is on and date is blank, current time will be used.</span>
                    @error('published_at')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </label>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <button type="submit" @click="isPublished = false; syncContent()"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 px-4 py-2 text-sm font-black text-gray-600 transition hover:bg-gray-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                        Draft
                    </button>
                    <button type="submit" @click="isPublished = true; syncContent()"
                        class="inline-flex min-h-11 items-center justify-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-black text-white shadow-sm transition hover:bg-violet-700">
                        {{ $isEdit ? 'Publish' : 'Create' }}
                    </button>
                </div>
                @if ($isEdit)
                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer"
                        class="mt-2 inline-flex min-h-11 w-full items-center justify-center rounded-lg bg-sky-50 px-4 py-2 text-sm font-black text-sky-700 transition hover:bg-sky-100 dark:bg-sky-500/15 dark:text-sky-300 dark:hover:bg-sky-500/25">
                        Preview
                    </a>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200/70 bg-white/60 p-4 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/40">
                <h2 class="text-sm font-black text-gray-900 dark:text-slate-100">Live Preview</h2>
                <div class="mt-3 max-h-[28rem] overflow-y-auto rounded-lg bg-gray-50 p-3 text-sm dark:bg-slate-900/60">
                    <div class="prose prose-sm max-w-none dark:prose-invert" x-html="renderContent()"></div>
                </div>
            </section>
        </aside>
    </form>
</div>

<script nonce="{{ $cspNonce }}">
function blogBlockEditor(config) {
    return {
        blocks: (config.initialBlocks || []).map((block, index) => ({ id: Date.now() + index, ...block })),
        isPublished: !!config.initialStatus,
        blockTypes: [
            { type: 'paragraph', label: 'Paragraph', short: 'P' },
            { type: 'image', label: 'Image', short: 'Img' },
            { type: 'heading', label: 'Heading', short: 'H' },
            { type: 'quote', label: 'Quote', short: 'Q' },
            { type: 'button', label: 'Button', short: 'Btn' },
            { type: 'video', label: 'Video', short: 'Vid' },
        ],
        blockLabel(type) {
            return this.blockTypes.find((item) => item.type === type) || { label: 'Existing Content', short: 'HTML' };
        },
        addBlock(type) {
            const defaults = {
                paragraph: { text: '' },
                image: { url: '', alt: '' },
                heading: { text: '' },
                quote: { text: '' },
                button: { text: 'Shop Now', url: '' },
                video: { url: '' },
            };
            this.blocks.push({ id: Date.now() + Math.random(), type, ...(defaults[type] || {}) });
            this.$nextTick(() => this.syncContent());
        },
        removeBlock(index) {
            this.blocks.splice(index, 1);
            this.syncContent();
        },
        moveBlock(index, direction) {
            const next = index + direction;
            if (next < 0 || next >= this.blocks.length) return;
            const item = this.blocks.splice(index, 1)[0];
            this.blocks.splice(next, 0, item);
            this.syncContent();
        },
        escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[char]));
        },
        paragraphHtml(text) {
            return this.escapeHtml(text).split(/\n{2,}/).map((part) => `<p>${part.replace(/\n/g, '<br>')}</p>`).join('');
        },
        videoHtml(url) {
            const safeUrl = this.escapeHtml(url);
            if (!safeUrl) return '';
            return `<p><a href="${safeUrl}" target="_blank" rel="noopener noreferrer">${safeUrl}</a></p>`;
        },
        renderBlock(block) {
            if (block.type === 'html') return block.html || '';
            if (block.type === 'paragraph') return this.paragraphHtml(block.text);
            if (block.type === 'heading') return block.text ? `<h2>${this.escapeHtml(block.text)}</h2>` : '';
            if (block.type === 'quote') return block.text ? `<blockquote>${this.paragraphHtml(block.text)}</blockquote>` : '';
            if (block.type === 'image') return block.url ? `<figure><img src="${this.escapeHtml(block.url)}" alt="${this.escapeHtml(block.alt)}"><figcaption>${this.escapeHtml(block.alt)}</figcaption></figure>` : '';
            if (block.type === 'button') return block.url ? `<p><a class="inline-flex rounded-xl bg-violet-600 px-5 py-3 font-bold text-white no-underline" href="${this.escapeHtml(block.url)}">${this.escapeHtml(block.text || 'Open')}</a></p>` : '';
            if (block.type === 'video') return this.videoHtml(block.url);
            return '';
        },
        renderContent() {
            return this.blocks.map((block) => this.renderBlock(block)).filter(Boolean).join('\n\n').trim();
        },
        syncContent() {
            this.$refs.contentInput.value = this.renderContent();
        },
    };
}
</script>
@endsection
