@extends('layouts.admin.app')

@php
    $isEdit = $post->exists;
    $contentValue = old('content', $post->content ?? '');
    
    $categoryOptions = array_values(array_unique(array_filter(array_merge(
        ['Tips & Tricks', 'How-to Guide', 'Product News', 'Announcements', 'Maintenance', 'Reviews'],
        \App\Models\Post::where('store_id', $store->id)->whereNotNull('category')->pluck('category')->all()
    ))));
    $previewUrl = $isEdit ? url('/blog/' . $post->slug . '?store_slug=' . $store->slug) : null;

    $inputClass = 'w-full rounded-lg border border-slate-200/90 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/60 px-3 py-2 text-xs font-sans text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 focus:bg-white dark:focus:bg-slate-800 transition outline-none';
    $labelClass = 'block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1';
    $helpClass = 'mt-1 text-[11px] text-slate-400 dark:text-slate-500 font-sans';
@endphp

@section('title', ($isEdit ? __('messages.blog_edit') : __('messages.blog_add_new')) . ' - ' . ($store->name ?? 'DataPOS'))
@section('main_padding', 'p-2')

@section('content')
<div class="w-full space-y-2 sm:space-y-2.5"
     x-data="richWordEditor({
         initialHtml: @js($contentValue),
         initialStatus: @js((bool) old('is_published', $post->is_published ?? true)),
     })">

    {{-- ============================================================
         PAGE HEADER — eyebrow badge, title, subtitle, CTA row
         ============================================================ --}}
    <header class="w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 bg-white dark:bg-slate-900 rounded-lg p-2.5 sm:p-3.5 border border-slate-200/80 dark:border-slate-800 shadow-2xs transition">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 text-[10px] sm:text-[11px] font-black uppercase tracking-wider border border-violet-100 dark:border-violet-900/60 mb-0.5">
                <span>📝</span>
                <span>{{ __('messages.sidebar_blog') }}</span>
                <span class="text-slate-400 dark:text-slate-500">·</span>
                <span class="font-normal normal-case text-slate-500 dark:text-slate-400">Word-style Document Editor</span>
            </div>
            <h1 class="text-base sm:text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                {{ $isEdit ? __('messages.blog_edit') : __('messages.blog_add_new') }}
            </h1>
            <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                {{ $store->name }} · {{ $isEdit ? $post->title : 'Write articles with formatting, images, headings and media' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0">
            @if ($isEdit)
                <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer"
                   class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800 transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span>Preview ↗</span>
                </a>
            @endif
            <a href="{{ route('store.admin.blog.index', ['store_slug' => $store->slug]) }}"
               class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition flex items-center gap-1.5 active:scale-95">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                <span>{{ __('messages.back') }}</span>
            </a>
        </div>
    </header>

    {{-- Flash Notifications & Errors --}}
    @if (session('success'))
        <div class="w-full p-2.5 sm:p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2 shadow-2xs">
            <span>✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="w-full p-2.5 sm:p-3 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-lg text-xs text-rose-800 dark:text-rose-300 space-y-1 shadow-2xs">
            @foreach ($errors->all() as $error)
                <div class="flex items-center gap-1.5 font-bold"><span>⚠️</span><span>{{ $error }}</span></div>
            @endforeach
        </div>
    @endif

    {{-- Main Form --}}
    <form method="POST" enctype="multipart/form-data"
          action="{{ $isEdit ? route('store.admin.blog.update', ['store_slug' => $store->slug, 'post' => $post->id]) : route('store.admin.blog.store', ['store_slug' => $store->slug]) }}"
          class="grid grid-cols-1 lg:grid-cols-3 gap-2 sm:gap-2.5 items-start"
          @submit="prepareSubmit()">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        {{-- Left 2 Columns: Document Workspace --}}
        <div class="lg:col-span-2 space-y-2 sm:space-y-2.5">
            
            {{-- 1. Article Title & Category Header --}}
            <section class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- Title --}}
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">Article Title <span class="text-rose-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $post->title) }}" required
                               class="{{ $inputClass }} font-bold text-sm sm:text-base"
                               placeholder="ဆောင်းပါး ခေါင်းစဉ် (e.g. ဖုန်းမျက်နှာပြင် မှန်ကပ် ရွေးချယ်နည်းနှင့် ထိန်းသိမ်းပုံ)" />
                        @error('title')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label class="{{ $labelClass }}">Category</label>
                        <select name="category" class="{{ $inputClass }} cursor-pointer font-bold">
                            <option value="">None (General)</option>
                            @foreach ($categoryOptions as $cat)
                                <option value="{{ $cat }}" {{ old('category', $post->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('category')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Featured Cover Image --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1 border-t border-slate-100 dark:border-slate-800">
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}">Featured Cover Image</label>
                        <input type="file" name="image" accept="image/png,image/jpeg,image/jpg,image/webp"
                               class="block w-full text-xs text-slate-500 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-violet-50 dark:file:bg-slate-800 file:text-violet-700 dark:file:text-violet-300 hover:file:bg-violet-100 cursor-pointer" />
                        <p class="{{ $helpClass }}">Recommended: 1200×750 px WebP/JPG (under 5MB)</p>
                        @error('image')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <span class="{{ $labelClass }}">Cover Preview</span>
                        @if (!empty($post->image_path))
                            <img src="{{ asset('storage/' . $post->image_path) }}" alt="Cover Preview" class="w-full h-14 rounded object-cover border border-slate-200 dark:border-slate-700" data-img-fallback="hide" />
                        @else
                            <div class="h-14 rounded border border-dashed border-slate-300 dark:border-slate-700 grid place-items-center text-[10px] font-bold text-slate-400">
                                No cover image
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            {{-- 2. WORD-STYLE RICH TEXT DOCUMENT CANVAS --}}
            <section class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 shadow-2xs overflow-hidden flex flex-col">
                
                {{-- WORD RIBBON TOOLBAR --}}
                <div class="sticky top-0 z-20 bg-slate-100 dark:bg-slate-800/95 backdrop-blur-xs border-b border-slate-200 dark:border-slate-700 p-2 flex flex-wrap items-center gap-1 sm:gap-1.5 text-xs select-none">
                    
                    {{-- Headings Dropdown --}}
                    <div class="inline-flex items-center">
                        <select @change="applyHeading($event.target.value); $event.target.value = ''"
                                class="h-8 px-2 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-bold focus:ring-1 focus:ring-violet-500 cursor-pointer">
                            <option value="">Normal Text (Paragraph)</option>
                            <option value="H2">Heading 2 (ခေါင်းစဉ်ကြီး)</option>
                            <option value="H3">Heading 3 (ခေါင်းစဉ်လတ်)</option>
                            <option value="H4">Heading 4 (ခေါင်းစဉ်ငယ်)</option>
                            <option value="p">Paragraph</option>
                        </select>
                    </div>

                    <div class="h-5 w-px bg-slate-300 dark:bg-slate-700 mx-0.5"></div>

                    {{-- Text Formatting Buttons --}}
                    <div class="inline-flex items-center gap-0.5 bg-white dark:bg-slate-900 p-0.5 rounded-lg border border-slate-200 dark:border-slate-700">
                        <button type="button" @click="exec('bold')" class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 grid place-items-center font-bold text-slate-700 dark:text-slate-200" title="Bold (Ctrl+B)">
                            <strong>B</strong>
                        </button>
                        <button type="button" @click="exec('italic')" class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 grid place-items-center italic text-slate-700 dark:text-slate-200 font-serif" title="Italic (Ctrl+I)">
                            <em>I</em>
                        </button>
                        <button type="button" @click="exec('underline')" class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 grid place-items-center underline text-slate-700 dark:text-slate-200" title="Underline (Ctrl+U)">
                            <u>U</u>
                        </button>
                        <button type="button" @click="exec('strikeThrough')" class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 grid place-items-center line-through text-slate-700 dark:text-slate-200 text-xs" title="Strikethrough">
                            S
                        </button>
                    </div>

                    <div class="h-5 w-px bg-slate-300 dark:bg-slate-700 mx-0.5"></div>

                    {{-- Lists & Quotation --}}
                    <div class="inline-flex items-center gap-0.5 bg-white dark:bg-slate-900 p-0.5 rounded-lg border border-slate-200 dark:border-slate-700">
                        <button type="button" @click="exec('insertUnorderedList')" class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 grid place-items-center text-slate-700 dark:text-slate-200 text-xs" title="Bullet List">
                            •≡
                        </button>
                        <button type="button" @click="exec('insertOrderedList')" class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 grid place-items-center text-slate-700 dark:text-slate-200 text-xs font-mono" title="Numbered List">
                            1.≡
                        </button>
                        <button type="button" @click="insertQuote()" class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 grid place-items-center text-slate-700 dark:text-slate-200 text-xs" title="Blockquote">
                            ❝
                        </button>
                        <button type="button" @click="exec('insertHorizontalRule')" class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 grid place-items-center text-slate-700 dark:text-slate-200 text-xs" title="Horizontal Divider Line">
                            ―
                        </button>
                    </div>

                    <div class="h-5 w-px bg-slate-300 dark:bg-slate-700 mx-0.5"></div>

                    {{-- Media & Inserts --}}
                    <div class="inline-flex items-center gap-1">
                        <button type="button" @click="insertLinkModal()" class="h-7 px-2 rounded-lg bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs flex items-center gap-1" title="Insert Link">
                            <span>🔗 Link</span>
                        </button>
                        <button type="button" @click="insertImageModal()" class="h-7 px-2 rounded-lg bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs flex items-center gap-1" title="Insert Image from URL">
                            <span>🖼️ Image</span>
                        </button>
                        <button type="button" @click="insertButtonModal()" class="h-7 px-2 rounded-lg bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs flex items-center gap-1" title="Insert Call-To-Action Button">
                            <span>🔘 Button</span>
                        </button>
                    </div>

                    <div class="h-5 w-px bg-slate-300 dark:bg-slate-700 mx-0.5"></div>

                    {{-- Starter Templates Dropdown --}}
                    <div class="inline-flex items-center">
                        <select @change="insertTemplate($event.target.value); $event.target.value = ''"
                                class="h-8 px-2 rounded-lg border border-violet-300 dark:border-violet-700 bg-violet-50 dark:bg-violet-950/60 text-violet-700 dark:text-violet-300 text-xs font-bold focus:ring-1 focus:ring-violet-500 cursor-pointer">
                            <option value="">⚡ Insert Template...</option>
                            <option value="tutorial">🛠️ How-to Guide / Tutorial</option>
                            <option value="review">📱 Product Review & Specs</option>
                            <option value="announcement">📢 Store News & Promo</option>
                            <option value="faq">❓ FAQ Question & Answers</option>
                        </select>
                    </div>

                    {{-- HTML Source / Visual Switch --}}
                    <div class="ml-auto flex items-center gap-1.5">
                        <button type="button" @click="toggleSourceMode()"
                                class="h-7 px-2.5 rounded-lg border text-xs font-bold transition flex items-center gap-1"
                                :class="sourceMode ? 'bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border-amber-300' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'">
                            <span x-text="sourceMode ? '👁️ Visual Editor' : '‹/› HTML Code'"></span>
                        </button>
                    </div>
                </div>

                {{-- DOCUMENT CANVAS (Word Paper Styling) --}}
                <div class="p-3 sm:p-5 bg-slate-50/40 dark:bg-slate-950/40 flex-1 min-h-[420px]">
                    
                    {{-- Visual WYSIWYG Editor --}}
                    <div x-show="!sourceMode"
                         x-ref="visualEditor"
                         contenteditable="true"
                         @input="updateFromVisual()"
                         @blur="updateFromVisual()"
                         class="w-full min-h-[380px] p-4 sm:p-6 bg-white dark:bg-slate-900 rounded-lg border border-slate-200/90 dark:border-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-violet-500/20 text-slate-800 dark:text-slate-100 font-sans text-sm leading-relaxed prose prose-slate dark:prose-invert max-w-none empty:before:content-[attr(data-placeholder)] empty:before:text-slate-400 empty:before:pointer-events-none"
                         data-placeholder="စတင်၍ ဆောင်းပါး ရေးသားပါ... (စာသား၊ ခေါင်းစဉ်၊ ပုံရိပ်များကို Word ပုံစံ လွတ်လပ်စွာ ရိုက်ထည့်နိုင်ပါသည်)">
                    </div>

                    {{-- Raw HTML Code Editor --}}
                    <textarea x-show="sourceMode"
                              x-model="htmlContent"
                              @input="updateFromSource()"
                              rows="18"
                              class="w-full min-h-[380px] p-4 bg-slate-900 text-emerald-400 font-mono text-xs rounded-lg border border-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                              placeholder="<html>...</html>"></textarea>
                </div>

                {{-- Document Footer Info Bar --}}
                <div class="px-4 py-2 bg-slate-100/80 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                    <div class="flex items-center gap-3">
                        <span>📊 <strong x-text="wordCount"></strong> words</span>
                        <span>⏱️ ~<strong x-text="readingTime"></strong> min read</span>
                    </div>
                    <div>
                        <span>Word-style Rich HTML Studio</span>
                    </div>
                </div>

                {{-- Hidden Form Field carrying clean HTML payload --}}
                <textarea name="content" x-ref="contentInput" class="hidden">{{ $contentValue }}</textarea>
                @error('content')<p class="p-2 text-xs font-semibold text-rose-600 bg-rose-50 dark:bg-rose-950/40 border-t border-rose-200">{{ $message }}</p>@enderror
            </section>

            {{-- 3. SEO & Social Meta Panel --}}
            <section class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-xs">🌐</span>
                    <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">SEO & Search Optimization</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">Custom Slug</label>
                        <input type="text" name="slug" value="{{ old('slug', $post->slug) }}" class="{{ $inputClass }} font-mono" placeholder="auto-generated-from-title" />
                        <p class="{{ $helpClass }}">Leave blank to auto-generate from title.</p>
                        @error('slug')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $post->meta_keywords) }}" class="{{ $inputClass }}" placeholder="phone, tempered glass, cctv" />
                        @error('meta_keywords')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="{{ $labelClass }}">Tags</label>
                        <input type="text" name="tags" value="{{ old('tags', $post->tags) }}" class="{{ $inputClass }}" placeholder="charging, screen protector, guide" />
                        @error('tags')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">Excerpt Summary</label>
                        <textarea name="excerpt" rows="2" maxlength="500" class="{{ $inputClass }}" placeholder="Short summary for card list preview">{{ old('excerpt', $post->excerpt) }}</textarea>
                        @error('excerpt')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="pt-1">
                    <label class="{{ $labelClass }}">Meta Description</label>
                    <textarea name="meta_description" rows="2" maxlength="1000" class="{{ $inputClass }}" placeholder="Google search result snippet description">{{ old('meta_description', $post->meta_description) }}</textarea>
                    @error('meta_description')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>
            </section>
        </div>

        {{-- Right 1 Column: Publishing Controls & Live Preview --}}
        <div class="space-y-2 sm:space-y-2.5 lg:sticky lg:top-2">
            {{-- Publish Box --}}
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-xs">🚀</span>
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">Publishing Status</h3>
                </div>

                {{-- Status Toggle --}}
                <label class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 cursor-pointer">
                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-slate-100 block" x-text="isPublished ? '● Published' : '○ Draft'"></span>
                        <span class="text-[10px] text-slate-400" x-text="isPublished ? 'Live on Storefront' : 'Hidden / Draft status'"></span>
                    </div>
                    <input type="checkbox" name="is_published" value="1" x-model="isPublished" class="w-4 h-4 rounded text-violet-600 focus:ring-violet-500" />
                </label>

                {{-- Schedule / Date --}}
                <div>
                    <label class="{{ $labelClass }}">Publish Date & Time</label>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="{{ $inputClass }} font-mono" />
                    <p class="{{ $helpClass }}">Leave blank to use current time upon publishing.</p>
                    @error('published_at')<p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                </div>

                {{-- Action Buttons --}}
                <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="submit" @click="isPublished = false; prepareSubmit()"
                            class="px-3 py-2 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200/80 dark:border-slate-700 transition active:scale-95 text-center">
                        Save Draft
                    </button>
                    <button type="submit" @click="isPublished = true; prepareSubmit()"
                            class="px-3 py-2 rounded-lg text-xs font-bold bg-violet-600 hover:bg-violet-700 text-white shadow-2xs transition active:scale-95 text-center">
                        {{ $isEdit ? 'Save & Publish' : 'Publish Post' }}
                    </button>
                </div>

                @if ($isEdit)
                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener noreferrer"
                       class="w-full py-2 rounded-lg text-xs font-bold bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800 transition flex items-center justify-center gap-1 active:scale-95 text-center">
                        <span>View Live on Storefront</span>
                        <span>↗</span>
                    </a>
                @endif
            </div>

            {{-- Live Preview Box --}}
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200/80 dark:border-slate-800 p-3 sm:p-4 shadow-2xs space-y-2">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                    <span class="text-xs">👁️</span>
                    <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-slate-200">Mobile & Web Live Preview</h3>
                </div>
                <div class="max-h-80 overflow-y-auto rounded-lg bg-slate-50 dark:bg-slate-800/60 p-3 text-xs border border-slate-100 dark:border-slate-800">
                    <div class="prose prose-xs max-w-none dark:prose-invert" x-html="htmlContent || '<p class=\'text-slate-400 italic\'>ဆောင်းပါးစာသားများ ဤနေရာတွင် Preview ပေါ်လာပါမည်...</p>'"></div>
                </div>
            </div>
        </div>
    </form>
</div>

<script nonce="{{ $cspNonce }}">
function richWordEditor(config) {
    return {
        htmlContent: config.initialHtml || '',
        isPublished: !!config.initialStatus,
        sourceMode: false,
        wordCount: 0,
        readingTime: 1,

        init() {
            this.$nextTick(() => {
                if (this.$refs.visualEditor) {
                    this.$refs.visualEditor.innerHTML = this.htmlContent;
                }
                this.calculateStats();
            });
        },

        exec(command, value = null) {
            if (this.sourceMode) return;
            this.$refs.visualEditor.focus();
            document.execCommand(command, false, value);
            this.updateFromVisual();
        },

        applyHeading(tag) {
            if (this.sourceMode) return;
            if (!tag) {
                document.execCommand('formatBlock', false, '<p>');
            } else {
                document.execCommand('formatBlock', false, '<' + tag + '>');
            }
            this.updateFromVisual();
        },

        insertQuote() {
            if (this.sourceMode) return;
            this.$refs.visualEditor.focus();
            document.execCommand('formatBlock', false, '<blockquote>');
            this.updateFromVisual();
        },

        insertLinkModal() {
            if (this.sourceMode) return;
            const url = prompt('Enter Link URL (e.g. https://example.com သို့မဟုတ် /products?category=cctv):', 'https://');
            if (url && url !== 'https://') {
                this.$refs.visualEditor.focus();
                document.execCommand('createLink', false, url);
                this.updateFromVisual();
            }
        },

        insertImageModal() {
            if (this.sourceMode) return;
            const url = prompt('Enter Image URL (e.g. https://images.unsplash.com/...):', 'https://');
            if (url && url !== 'https://') {
                const alt = prompt('Enter Image Caption / Description:', 'Photo') || '';
                const html = `<figure class="my-3"><img src="${url}" alt="${alt}" class="rounded-lg max-h-72 w-auto object-cover mx-auto" /><figcaption class="text-center text-xs text-slate-400 mt-1 italic">${alt}</figcaption></figure><p></p>`;
                this.$refs.visualEditor.focus();
                document.execCommand('insertHTML', false, html);
                this.updateFromVisual();
            }
        },

        insertButtonModal() {
            if (this.sourceMode) return;
            const text = prompt('Enter Button Label (e.g. Shop Now / အခုဝယ်ယူမည်):', 'Shop Now');
            if (!text) return;
            const url = prompt('Enter Destination URL:', 'https://') || '#';
            const html = `<p class="my-3 text-center"><a href="${url}" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg bg-violet-600 text-white font-bold text-xs no-underline hover:bg-violet-700 shadow">${text} →</a></p><p></p>`;
            this.$refs.visualEditor.focus();
            document.execCommand('insertHTML', false, html);
            this.updateFromVisual();
        },

        insertTemplate(type) {
            if (!type) return;
            let templateHtml = '';
            if (type === 'tutorial') {
                templateHtml = `
<h2>📌 နိဒါန်း (Introduction)</h2>
<p>ဤလမ်းညွှန်တွင် အသုံးပြုသူများအတွက် အရေးပါသော အဆင့်ဆင့်လုပ်ဆောင်ချက်များကို အသေးစိတ် ရှင်းပြပေးသွားပါမည်။</p>

<h2>🛠️ လိုအပ်သော ပစ္စည်းများနှင့် ကြိုတင်ပြင်ဆင်မှုများ</h2>
<ul>
    <li>ပစ္စည်း (၁) - အသေးစိတ် ဖော်ပြချက်</li>
    <li>ပစ္စည်း (၂) - အသေးစိတ် ဖော်ပြချက်</li>
    <li>သန့်ရှင်းသော အဝတ်နု သို့မဟုတ် သန့်ရှင်းရေးသုံး ပစ္စည်း</li>
</ul>

<h2>📝 အဆင့်ဆင့် လုပ်ဆောင်နည်းလမ်း</h2>
<p><strong>အဆင့် (၁) -</strong> ပထမဆုံးအနေဖြင့် ပစ္စည်းကို သေချာစွာ စစ်ဆေးပါ။</p>
<p><strong>အဆင့် (၂) -</strong> ညွှန်ကြားချက်အတိုင်း သေသပ်စွာ တပ်ဆင်ပါ။</p>

<blockquote>💡 <strong>အကြံပြုချက်:</strong> အသုံးပြုနေစဉ် အမှားအယွင်းမဖြစ်စေရန် သတိထားဆောင်ရွက်ပါ။</blockquote>

<h2>🎯 နိဂုံးချုပ် (Conclusion)</h2>
<p>အထက်ပါ နည်းလမ်းများကို လိုက်နာခြင်းဖြင့် သင့်ဖုန်း/ပစ္စည်းကို ရေရှည်စိတ်ချစွာ အသုံးပြုနိုင်မည်ဖြစ်ပါသည်။</p>
<p></p>
`;
            } else if (type === 'review') {
                templateHtml = `
<h2>✨ ပစ္စည်းအကြောင်း မိတ်ဆက်</h2>
<p>ယခုတစ်ပတ်တွင် လူကြိုက်များပြီး အရည်အသွေးမြင့်မားသော ပစ္စည်းအသစ်အကြောင်း သုံးသပ်ဖော်ပြပေးပါမည်။</p>

<h2>⚙️ အဓိက သတ်မှတ်ချက်များ (Key Specifications)</h2>
<ul>
    <li><strong>ဒီဇိုင်း & အရွယ်အစား:</strong> ပေါ့ပါးကျစ်လျစ်သော ပုံစံ</li>
    <li><strong>ခံနိုင်ရည်:</strong> အကြမ်းခံ အရည်အသွေးမြင့် ကုန်ကြမ်းများ</li>
    <li><strong>အာမခံ:</strong> တရားဝင် အာမခံ ပါဝင်မှု</li>
</ul>

<h2>👍 အားသာချက်များနှင့် သဘောကျမိသောအချက်များ</h2>
<p>စျေးနှုန်းနှင့်ယှဉ်လျှင် အလွန်တန်ဖိုးရှိပြီး သုံးစွဲရ လွယ်ကူအဆင်ပြေပါသည်။</p>

<p class="text-center my-4"><a href="#" class="inline-flex px-4 py-2 bg-violet-600 text-white font-bold rounded-lg no-underline">စတိုးတွင် အသေးစိတ်ကြည့်ရှုရန် →</a></p>
<p></p>
`;
            } else if (type === 'announcement') {
                templateHtml = `
<h2>📢 အထူးပရိုမိုးရှင်းနှင့် သတင်းကောင်း</h2>
<p>ကျွန်ုပ်တို့၏ Customer များအတွက် အထူးလျှော့စျေးနှင့် လက်ဆောင်အစီအစဉ်များ စတင်လိုက်ပါပြီ။</p>

<blockquote>🎉 <strong>သတ်မှတ်ကာလ:</strong> ယခုလကုန်အထိသာ အကျုံးဝင်ပါသည်။</blockquote>

<h2>🎁 ပရိုမိုးရှင်း အစီအစဉ်များ</h2>
<ul>
    <li>ပစ္စည်းအားလုံးအတွက် ၁၀% မှ ၃၀% အထိ လျှော့စျေး</li>
    <li>ဝယ်ယူမှုတိုင်းအတွက် အထူးလက်ဆောင်များ</li>
</ul>
<p></p>
`;
            } else if (type === 'faq') {
                templateHtml = `
<h2>❓ မကြာခဏ မေးလေ့ရှိသော မေးခွန်းများ (FAQ)</h2>

<h3>မေး။ ပစ္စည်းမှာယူပြီး မည်မျှကြာလျှင် ရောက်ရှိမည်နည်း?</h3>
<p>ဖြေ။ ရန်ကုန်/မန္တလေး အတွင်း ၁-၂ ရက်နှင့် အခြားမြို့များသို့ ၂-၃ ရက်အတွင်း ပို့ဆောင်ပေးပါသည်။</p>

<h3>မေး။ ပစ္စည်းချို့ယွင်းပါက မည်သို့လဲလှယ်နိုင်ပါသလဲ?</h3>
<p>ဖြေ။ အာမခံကာလအတွင်း ချို့ယွင်းမှုရှိပါက ဆိုင်သို့ တိုက်ရိုက်ဆက်သွယ် လဲလှယ်နိုင်ပါသည်။</p>
<p></p>
`;
            }

            if (this.sourceMode) {
                this.htmlContent += templateHtml;
            } else {
                this.$refs.visualEditor.focus();
                document.execCommand('insertHTML', false, templateHtml);
                this.updateFromVisual();
            }
        },

        toggleSourceMode() {
            if (this.sourceMode) {
                // Switching from Code to Visual
                this.$refs.visualEditor.innerHTML = this.htmlContent;
                this.sourceMode = false;
            } else {
                // Switching from Visual to Code
                this.htmlContent = this.$refs.visualEditor.innerHTML;
                this.sourceMode = true;
            }
            this.calculateStats();
        },

        updateFromVisual() {
            if (this.$refs.visualEditor) {
                this.htmlContent = this.$refs.visualEditor.innerHTML;
                this.syncFormInput();
                this.calculateStats();
            }
        },

        updateFromSource() {
            this.syncFormInput();
            this.calculateStats();
        },

        syncFormInput() {
            if (this.$refs.contentInput) {
                this.$refs.contentInput.value = this.htmlContent;
            }
        },

        calculateStats() {
            const plainText = (this.htmlContent || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
            const words = plainText ? plainText.split(' ').filter(Boolean).length : 0;
            this.wordCount = words;
            this.readingTime = Math.max(1, Math.ceil(words / 200));
        },

        prepareSubmit() {
            if (!this.sourceMode && this.$refs.visualEditor) {
                this.htmlContent = this.$refs.visualEditor.innerHTML;
            }
            this.syncFormInput();
        }
    };
}
</script>
@endsection
